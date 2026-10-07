<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisEligibilityAndErrorsTest extends TestCase
{
    private function signIn(string $role = 'admin', int $calls = 1): void
    {
        $user = new User;
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->times($calls)->andReturn('fixture-token');
        $this->app->instance(TokenService::class, $tokens);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.cache_store', 'array');
        Http::preventStrayRequests();
        Log::spy();
    }

    #[DataProvider('administrativeRoles')]
    public function test_slot_eligibility_uses_confirmed_fields_and_preserves_provider_results(string $role): void
    {
        $this->signIn($role);
        $payload = ['filtre' => ['creneauId' => 'slot-id', 'numeroDossier' => ['query' => '01234567890909', 'match' => 'EXACT']]];
        $results = [
            ['candidat' => ['id' => 'candidate-1', 'numeroDossier' => '01234567890909'], 'eligibilite' => ['estEligible' => true]],
            ['candidat' => ['id' => 'candidate-2'], 'eligibilite' => ['estEligible' => false, 'codeMotif' => 'CANDIDAT_SOUS_PENALITE', 'motif' => 'Sous pénalité']],
        ];
        Http::fake(['https://api.example.test/api/v2/auto-ecole/candidats/recherche' => Http::response($results)]);
        $this->postJson('/api/rdvpermis/candidats/recherche', $payload)->assertOk()->assertExactJson($results);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/api/v2/auto-ecole/candidats/recherche'
            && $request->method() === 'POST' && $request->data() === $payload);
    }

    public static function administrativeRoles(): array
    {
        return [['admin'], ['secretary'], ['super-admin']];
    }

    public function test_invalid_or_missing_slot_never_calls_provider(): void
    {
        $this->signIn('admin', 0);
        foreach ([[], ['filtre' => []], ['filtre' => ['creneauId' => 123]], ['filtre' => ['creneauId' => 'slot', 'groupePermis' => 'B']]] as $payload) {
            $this->postJson('/api/rdvpermis/candidats/recherche', $payload)->assertUnprocessable();
        }
        Http::assertNothingSent();
    }

    public function test_eligibility_route_requires_authentication_and_an_administrative_role(): void
    {
        $this->postJson('/api/rdvpermis/candidats/recherche', [])->assertUnauthorized();
        $this->signIn('student', 0);
        $this->postJson('/api/rdvpermis/candidats/recherche', [])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_provider_unknown_slot_is_a_safe_business_error(): void
    {
        $this->signIn();
        Http::fake(['*' => Http::response(['code' => 'CRENEAU_INCONNU', 'private' => 'secret'], 400)]);
        $this->postJson('/api/rdvpermis/candidats/recherche', ['filtre' => ['creneauId' => 'slot']])
            ->assertBadRequest()->assertExactJson(['message' => 'Le créneau demandé est introuvable dans RdvPermis.']);
    }

    #[DataProvider('ineligibleResults')]
    public function test_assignment_is_blocked_without_provider_confirmation(array $results, int $status): void
    {
        $this->signIn();
        Http::fake(['https://api.example.test/api/v2/auto-ecole/candidats/recherche' => Http::response($results)]);
        $this->putJson('/api/rdvpermis/paniers/basket/creneaux/slot/candidat', ['candidatId' => 'candidate'])
            ->assertStatus($status);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
    }

    public static function ineligibleResults(): array
    {
        return [
            [[], 422],
            [[['candidat' => ['id' => 'candidate'], 'eligibilite' => ['estEligible' => false]]], 422],
            [[['candidat' => ['id' => 'different'], 'eligibilite' => ['estEligible' => true]]], 422],
            [[['candidat' => ['id' => 'candidate']]], 422],
            [['unexpected' => 'format'], 502],
        ];
    }

    #[DataProvider('retryHeaders')]
    public function test_rate_limit_returns_safe_retry_after_and_suppresses_immediate_followup(?string $header, int $expected): void
    {
        $this->freezeTime();
        $this->signIn();
        $headers = $header === null ? [] : ['Retry-After' => $header === 'date' ? now()->addSeconds(45)->toRfc7231String() : $header];
        Http::fake(['*' => Http::response(['private' => 'provider detail'], 429, $headers)]);
        $this->getJson('/api/rdvpermis/current-school')->assertStatus(429)->assertHeader('Retry-After', (string) $expected)->assertJsonPath('retry_after', $expected);
        // The cooldown applies across API endpoints for the same actor and client.
        $this->getJson('/api/rdvpermis/employees')->assertStatus(429)->assertHeader('Retry-After', (string) $expected);
        Http::assertSentCount(1);
    }

    public static function retryHeaders(): array
    {
        return [['30', 30], ['date', 45], [null, 60], ['invalid-value', 60], ['999999999', 86400]];
    }

    #[DataProvider('businessErrors')]
    public function test_confirmed_business_codes_are_mapped_safely(string $code, string $message): void
    {
        $this->signIn();
        Http::fake(['*' => Http::response(['code' => $code, 'detail' => 'private'], 400)]);
        $this->getJson('/api/rdvpermis/current-school')->assertBadRequest()->assertExactJson(['message' => $message]);
    }

    public static function businessErrors(): array
    {
        return [
            ['PANIER_PLEIN', 'Le panier RdvPermis est plein.'],
            ['CRENEAU_NON_DISPONIBLE', 'Ce créneau n’est plus disponible dans RdvPermis.'],
            ['CRENEAU_NON_RESERVABLE_SEUIL_ATTEINT', 'Le seuil de réservation RdvPermis est atteint pour ce créneau.'],
            ['CRENEAU_NON_RESERVABLE_PLAFOND_ATTEINT', 'Le plafond de réservation RdvPermis est atteint pour ce créneau.'],
            ['CRENEAU_DE_GROUPE_DIFFERENT', 'Le groupe de permis du créneau est incompatible.'],
            ['AUTO_ECOLE_NA_AUCUNE_DECLARATION', 'L’auto-école ne dispose d’aucune déclaration dans RdvPermis.'],
        ];
    }

    public function test_internal_failures_return_generic_500_even_with_debug_enabled(): void
    {
        config()->set('app.debug', true);
        $this->signIn('admin', 0);
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->andThrow(new \RuntimeException('SQL and secret should never be returned'));
        $this->app->instance(TokenService::class, $tokens);
        $response = $this->getJson('/api/rdvpermis/current-school')->assertStatus(500);
        $this->assertStringNotContainsString('SQL and secret', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_database_failure_before_mandate_controller_does_not_expose_sql(): void
    {
        config()->set('app.debug', true);
        $this->signIn('admin', 0);
        $response = $this->postJson('/api/admin/students/a2b04899-c164-462b-836f-74bd5327c163/rdvpermis/mandate', [])
            ->assertStatus(500)->assertExactJson(['message' => 'La demande RdvPermis n’a pas pu être traitée. Réessayez plus tard.']);
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_business_code_in_server_failure_does_not_override_transport_classification(): void
    {
        $this->signIn();
        Http::fake(['*' => Http::response(['code' => 'PANIER_PLEIN'], 503)]);
        $this->getJson('/api/rdvpermis/current-school')->assertStatus(502)
            ->assertExactJson(['message' => 'Le service RdvPermis rencontre une erreur temporaire. Réessayez plus tard.']);
    }
}
