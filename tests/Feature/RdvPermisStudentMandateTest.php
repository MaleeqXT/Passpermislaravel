<?php

namespace Tests\Feature;

use App\Models\RdvPermisSyncRecord;
use App\Models\RdvPermisToken;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisStudentMandateTest extends TestCase
{
    private const STUDENT_ID = 'a2b04899-c164-462b-836f-74bd5327c163';

    private const ROUTE = '/api/admin/students/'.self::STUDENT_ID.'/rdvpermis/mandate';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'rdvpermis_mandate_test');
        config()->set('database.connections.rdvpermis_mandate_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        DB::purge('rdvpermis_mandate_test');
        DB::setDefaultConnection('rdvpermis_mandate_test');
        config()->set('session.driver', 'array');
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();

        (require database_path('migrations/2024_12_24_000006_create_users_table.php'))->up();
        (require database_path('migrations/2024_12_31_200006_create_students_table.php'))->up();
        (require database_path('migrations/2026_10_07_000002_store_neph_as_text.php'))->up();
        (require database_path('migrations/2024_12_31_210115_create_monitors_table.php'))->up();
        (require database_path('migrations/2025_09_25_071630_create_secretaries_table.php'))->up();
        (require database_path('migrations/2026_08_22_000001_create_rdvpermis_sync_records_table.php'))->up();
        (require database_path('migrations/2026_10_07_000003_add_rdvpermis_candidate_mapping.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('rdvpermis_mandate_test');
        parent::tearDown();
    }

    public function test_it_builds_the_confirmed_mandate_payload_from_the_database_and_tracks_the_remote_id(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        $providerResponse = [
            'id' => 'mandate-123',
            'candidatId' => 'candidate-456',
            'autoEcole' => ['id' => 'school-789'],
            'groupePermis' => 'B',
        ];
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response($providerResponse, 201)]);

        $response = $this->postJson(self::ROUTE, [])
            ->assertCreated()
            ->assertExactJson($providerResponse);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/api/v2/auto-ecole/mandats'
            && $request->method() === 'POST'
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Authorization', 'Bearer imported-swagger-token')
            && $request->data() === [
                'nom' => 'Dupont',
                'numeroDossier' => '01234567890909',
                'groupePermis' => 'B',
                'email' => 'louis.dupont@example.test',
            ]);
        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('student_rdvpermis_mandate', $record->entity_type);
        $this->assertSame(self::STUDENT_ID, $record->entity_id);
        $this->assertSame('mandate-123', $record->remote_id);
        $this->assertSame('candidate-456', $record->remote_candidate_id);
        $this->assertSame('school-789', $record->remote_school_id);
        $this->assertSame('B', $record->permit_group);
        $this->assertNotNull($record->synced_at);
        $this->assertSame('https://api.example.test', $record->provider_context['api_url']);
        $this->assertSame('synced', $record->status);
        $this->assertSame(1, $record->sync_attempts);
        $this->assertStringNotContainsString('imported-swagger-token', $response->getContent());
    }

    public function test_it_uses_category_b_without_a_request_body_or_transmission_mapping(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response(['id' => 'mandate-123'], 201)]);

        $this->postJson(self::ROUTE, [])->assertCreated();
        Http::assertSent(fn ($request) => $request->data()['groupePermis'] === 'B');
    }

    public function test_missing_local_email_is_omitted_and_provider_can_accept_existing_candidate(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909', 'Dupont', null);
        $this->bindValidToken($actor);
        Http::fake(['*' => Http::response(['id' => 'mandate', 'candidatId' => 'candidate'], 201)]);
        $this->postJson(self::ROUTE, [])->assertCreated();
        Http::assertSent(fn ($request) => ! array_key_exists('email', $request->data()));
        $this->assertSame('candidate', RdvPermisSyncRecord::query()->sole()->remote_candidate_id);
    }

    public function test_provider_email_requirement_is_authoritative_when_local_email_is_missing(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909', 'Dupont', null);
        $this->bindValidToken($actor);
        Http::fake(['*' => Http::response(['code' => 'EMAIL_MANQUANT'], 400)]);
        $this->postJson(self::ROUTE, [])->assertBadRequest()
            ->assertExactJson(['message' => 'Une adresse e-mail est requise pour ce candidat dans RdvPermis.']);
        Http::assertSentCount(1);
    }

    public function test_invalid_provided_email_is_rejected_locally(): void
    {
        $this->signIn('admin');
        $this->seedStudent('01234567890909', 'Dupont', 'invalid-email');
        $this->postJson(self::ROUTE, [])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_successful_mapping_survives_a_later_incomplete_success(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->twice()->andReturn('fixture-token');
        $this->app->instance(TokenService::class, $tokens);
        Http::fake(['*' => Http::sequence()->push([
            'id' => 'mandate', 'candidatId' => 'candidate', 'autoEcole' => ['id' => 'school'], 'groupePermis' => 'B',
        ], 201)->push([], 201)]);
        $this->postJson(self::ROUTE, [])->assertCreated();
        $this->postJson(self::ROUTE, [])->assertCreated();
        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('mandate', $record->remote_id);
        $this->assertSame('candidate', $record->remote_candidate_id);
        $this->assertSame('school', $record->remote_school_id);
        $this->assertSame(2, $record->sync_attempts);
    }

    #[DataProvider('missingLocalValues')]
    public function test_it_validates_required_local_candidate_fields_before_the_provider_call(string $field): void
    {
        $this->signIn('secretary');
        $this->seedStudent($field === 'neph' ? null : '01234567890909', $field === 'last_name' ? null : 'Dupont', $field === 'email' ? null : 'louis.dupont@example.test');
        Http::fake();

        $this->postJson(self::ROUTE, [])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
        Http::assertNothingSent();
        $this->assertSame(0, RdvPermisSyncRecord::count());
    }

    public static function missingLocalValues(): array
    {
        return [['neph'], ['last_name']];
    }

    public function test_guests_and_non_administrative_users_cannot_send_a_mandate(): void
    {
        $this->seedStudent('01234567890909');
        Http::fake();
        $this->postJson(self::ROUTE, [])->assertUnauthorized();

        $this->signIn('student');
        $this->postJson(self::ROUTE, [])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_candidate_unknown_is_sanitized_and_marks_the_sync_as_failed(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response([
            'erreur' => 'CANDIDAT_INCONNU',
            'access_token' => 'provider-private-token',
            'detail' => 'private provider detail',
        ], 400)]);

        $response = $this->postJson(self::ROUTE, [])
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Le candidat est inconnu de RdvPermis.']);

        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('failed', $record->status);
        foreach (['provider-private-token', 'private provider detail'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
            $this->assertStringNotContainsString($secret, (string) $record->last_error);
        }
    }

    public function test_email_conflict_is_sanitized_and_marks_the_sync_as_failed(): void
    {
        $actor = $this->signIn('secretary');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response([
            'code' => 'EMAIL_DEJA_ATTRIBUE',
            'refresh_token' => 'provider-private-refresh-token',
        ], 409)]);

        $response = $this->postJson(self::ROUTE, [])
            ->assertConflict()
            ->assertExactJson(['message' => 'Cette adresse e-mail est déjà attribuée dans RdvPermis.']);

        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('failed', $record->status);
        $this->assertStringNotContainsString('provider-private-refresh-token', $response->getContent());
        $this->assertStringNotContainsString('provider-private-refresh-token', (string) $record->last_error);
    }

    #[DataProvider('documentedMandateBusinessErrors')]
    public function test_documented_mandate_business_errors_are_safe_and_preserve_the_provider_status(string $code, string $message): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response([
            'code' => $code,
            'access_token' => 'provider-private-token',
        ], 422)]);

        $response = $this->postJson(self::ROUTE, [])
            ->assertStatus(422)
            ->assertExactJson(['message' => $message]);

        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('failed', $record->status);
        $this->assertStringNotContainsString('provider-private-token', $response->getContent());
        $this->assertStringNotContainsString('provider-private-token', (string) $record->last_error);
    }

    public static function documentedMandateBusinessErrors(): array
    {
        return [
            'candidate has no active application' => [
                'CANDIDAT_SANS_DEMANDE_ACTIVE',
                'Le candidat ne dispose pas de demande active dans RdvPermis.',
            ],
            'candidate email missing' => [
                'EMAIL_MANQUANT',
                'Une adresse e-mail est requise pour ce candidat dans RdvPermis.',
            ],
            'candidate is mandated to another school' => [
                'CANDIDAT_DEJA_SOUS_MANDAT',
                'Le candidat est déjà sous mandat d’une autre auto-école.',
            ],
            'candidate is already mandated to this school' => [
                'CANDIDAT_DEJA_SOUS_MON_MANDAT',
                'Le candidat est déjà sous mandat de cette auto-école.',
            ],
        ];
    }

    public function test_staging_returns_safe_mandate_diagnostics_and_logs_a_redacted_provider_body(): void
    {
        $this->app['env'] = 'staging';
        Log::spy();
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindValidToken($actor);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/mandats' => Http::response([
            'erreur' => 'MANDAT_INVALIDE',
            'message' => 'Le mandat ne respecte pas les règles RdvPermis.',
            'access_token' => 'provider-private-access-token',
            'refresh_token' => 'provider-private-refresh-token',
            'client_secret' => 'provider-private-client-secret',
        ], 400)]);

        $response = $this->postJson(self::ROUTE, [])
            ->assertBadRequest()
            ->assertExactJson([
                'message' => 'RdvPermis a refusé les données envoyées.',
                'rdvpermis_status' => 400,
                'rdvpermis_error' => 'MANDAT_INVALIDE',
            ]);

        Log::shouldHaveReceived('warning')->once()->with(
            'RdvPermis API request failed',
            Mockery::on(fn (array $context) => $context['endpoint'] === '/api/v2/auto-ecole/mandats'
                && $context['http_status'] === 400
                && $context['rdvpermis_error'] === 'MANDAT_INVALIDE'
                && $context['rdvpermis_message'] === 'Le mandat ne respecte pas les règles RdvPermis.'
                && ! str_contains((string) $context['rdvpermis_body'], 'provider-private-access-token')
                && ! str_contains((string) $context['rdvpermis_body'], 'provider-private-refresh-token')
                && ! str_contains((string) $context['rdvpermis_body'], 'provider-private-client-secret')),
        );
        $this->assertStringNotContainsString('provider-private-access-token', $response->getContent());
        $this->assertStringNotContainsString('provider-private-refresh-token', $response->getContent());
        $this->assertStringNotContainsString('provider-private-client-secret', $response->getContent());
    }

    public function test_an_expired_imported_token_never_sends_the_mandate(): void
    {
        $actor = $this->signIn('admin');
        $this->seedStudent('01234567890909');
        $this->bindExpiredTokenWithoutRefresh($actor);
        Http::fake();

        $response = $this->postJson(self::ROUTE, [])->assertUnauthorized();
        $this->assertStringNotContainsString('expired-imported-token', $response->getContent());
        Http::assertNothingSent();
        $this->assertSame('failed', RdvPermisSyncRecord::query()->sole()->status);
    }

    private function seedStudent(?string $neph, ?string $lastName = 'Dupont', ?string $email = 'louis.dupont@example.test'): void
    {
        DB::table('users')->insert([
            'id' => 1, 'first_name' => 'Louis', 'last_name' => $lastName ?? '',
            'name' => trim('Louis '.($lastName ?? '')), 'email' => $email ?? '', 'password' => 'fixture',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('students')->insert([
            'id' => self::STUDENT_ID, 'user_id' => 1, 'neph' => $neph,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function signIn(string $role): User
    {
        $user = new User;
        $user->id = 'rdv-actor';
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindValidToken(User $actor): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($actor)->andReturn('imported-swagger-token');
        $this->app->instance(TokenService::class, $tokens);
    }

    private function bindExpiredTokenWithoutRefresh(User $actor): void
    {
        $token = new class extends RdvPermisToken
        {
            public function update(array $attributes = [], array $options = []): bool
            {
                $this->forceFill($attributes);

                return true;
            }
        };
        $token->user_id = $actor->id;
        $token->status = 'connected';
        $token->access_token = 'expired-imported-token';
        $token->access_token_expires_at = now()->subMinute();
        $token->refresh_token = null;
        $token->setRelation('user', $actor);

        $service = new class($token) extends TokenService
        {
            public function __construct(private RdvPermisToken $token) {}

            public function forUser(User $user): ?RdvPermisToken
            {
                return $this->token;
            }
        };
        $this->app->instance(TokenService::class, $service);
    }
}
