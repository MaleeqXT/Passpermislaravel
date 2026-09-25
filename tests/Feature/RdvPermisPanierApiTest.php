<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisPanierApiTest extends TestCase
{
    private const PANIER_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
    }

    public function test_authorized_user_can_list_paniers(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $fixture = [['id' => self::PANIER_ID, 'dateHeureExpiration' => '2026-09-25T11:30:00+00:00']];
        Http::fake(['https://api.example.test/api/v2/auto-ecole/paniers' => Http::response($fixture)]);

        $this->getJson('/api/rdvpermis/paniers')->assertOk()->assertExactJson($fixture);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://api.example.test/api/v2/auto-ecole/paniers'
            && $request->body() === '');
    }

    public function test_panier_creation_allows_an_omitted_employee_and_forwards_location(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $fixture = ['id' => self::PANIER_ID, 'elementsDuPanier' => []];
        Http::fake(['https://api.example.test/api/v2/auto-ecole/paniers' => Http::response(
            $fixture,
            201,
            ['Location' => '/api/v2/auto-ecole/paniers/'.self::PANIER_ID],
        )]);

        $this->postJson('/api/rdvpermis/paniers')->assertCreated()->assertExactJson($fixture)
            ->assertHeader('Location', '/api/v2/auto-ecole/paniers/'.self::PANIER_ID);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://api.example.test/api/v2/auto-ecole/paniers'
            && $request->body() === '');
    }

    public function test_panier_creation_forwards_the_optional_employee_id(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $employeeId = '22222222-2222-4222-8222-222222222222';
        Http::fake(['https://api.example.test/api/v2/auto-ecole/paniers' => Http::response(['id' => self::PANIER_ID], 201)]);

        $this->postJson('/api/rdvpermis/paniers', ['employeAutoEcoleId' => $employeeId])->assertCreated();

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://api.example.test/api/v2/auto-ecole/paniers'
            && $request->data() === ['employeAutoEcoleId' => $employeeId]);
    }

    #[DataProvider('employeeErrors')]
    public function test_panier_creation_maps_documented_employee_errors_safely(string $code, string $message): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/paniers' => Http::response([
            'erreur' => $code,
            'access_token' => 'provider-private-token',
        ], 400)]);

        $response = $this->postJson('/api/rdvpermis/paniers')->assertBadRequest()
            ->assertExactJson(['message' => $message]);

        $this->assertStringNotContainsString('provider-private-token', $response->getContent());
    }

    public static function employeeErrors(): array
    {
        return [
            'unknown employee' => [
                'EMPLOYE_AUTO_ECOLE_INEXISTANT',
                'L’employé auto-école indiqué est introuvable dans RdvPermis.',
            ],
            'employee belongs to another school' => [
                'EMPLOYE_N_APPARTIENT_PAS_A_AUTO_ECOLE',
                'L’employé indiqué n’appartient pas à cette auto-école dans RdvPermis.',
            ],
        ];
    }

    public function test_authorized_user_can_get_and_delete_a_panier(): void
    {
        $user = $this->signIn();
        $this->bindToken($user, 2);
        $url = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID;
        Http::fake([$url => Http::sequence()
            ->push(['id' => self::PANIER_ID, 'elementsDuPanier' => []])
            ->push('', 204)]);

        $this->getJson('/api/rdvpermis/paniers/'.self::PANIER_ID)->assertOk()->assertJsonPath('id', self::PANIER_ID);
        $this->deleteJson('/api/rdvpermis/paniers/'.self::PANIER_ID)->assertNoContent();

        Http::assertSentCount(2);
    }

    #[DataProvider('validationResults')]
    public function test_panier_validation_forwards_complete_and_partial_reservation_results(int $status, array $fixture): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $url = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID.'/valider';
        Http::fake([$url => Http::response($fixture, $status)]);

        $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/valider')
            ->assertStatus($status)
            ->assertExactJson($fixture);

        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request->url() === $url && $request->body() === '');
    }

    public static function validationResults(): array
    {
        return [
            'all slots reserved' => [200, [
                'panierId' => self::PANIER_ID,
                'contientAuMoinsUneErreur' => false,
                'data' => [['creneauId' => 'slot-1', 'examenId' => 'exam-1', 'estReserve' => true]],
            ]],
            'partial business result' => [207, [
                'panierId' => self::PANIER_ID,
                'contientAuMoinsUneErreur' => true,
                'data' => [['creneauId' => 'slot-1', 'codeMessage' => 'CANDIDAT_SOUS_PENALITE', 'estReserve' => false]],
            ]],
        ];
    }

    #[DataProvider('validationErrors')]
    public function test_panier_validation_handles_documented_errors(int $upstreamStatus, int $expectedStatus): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $url = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID.'/valider';
        Http::fake([$url => Http::response(['erreur' => 'provider-private-error'], $upstreamStatus)]);

        $response = $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/valider')
            ->assertStatus($expectedStatus)
            ->assertJsonStructure(['message']);

        $this->assertStringNotContainsString('provider-private-error', $response->getContent());
    }

    public static function validationErrors(): array
    {
        return [
            'action forbidden' => [403, 403],
            'panier unknown' => [404, 404],
        ];
    }

    public function test_guests_cannot_call_panier_routes(): void
    {
        Http::fake();

        $this->getJson('/api/rdvpermis/paniers')->assertUnauthorized();
        $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/valider')->assertUnauthorized();
        Http::assertNothingSent();
    }

    private function signIn(): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindToken(User $user, int $times = 1): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->times($times)->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
