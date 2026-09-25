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

class RdvPermisPlanningApiTest extends TestCase
{
    private const CENTRE_ID = '264af7a0-bdc6-49b3-a567-bf4e09f55d64';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
    }

    public function test_planning_search_forwards_the_exact_nested_filter_and_preserves_documented_slot_states(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $fixture = [
            $this->planningResult('DISPONIBLE'),
            array_replace_recursive($this->planningResult('RÉSERVÉ_PAR_MON_AUTO_ÉCOLE'), [
                'examenDuPlanning' => [
                    'id' => '6060e828-bbce-4fbe-9cd4-c11d0df889cf',
                    'candidat' => ['id' => 'candidate-1', 'nom' => 'Dupont', 'prenom' => 'Louis'],
                    'estModifiable' => true,
                    'estAnnule' => false,
                ],
            ]),
            $this->planningResult('SEUIL_ATTEINT'),
        ];
        $url = 'https://api.example.test/api/v2/auto-ecole/planning/recherche';
        Http::fake([$url => Http::response($fixture)]);

        $this->postJson('/api/rdvpermis/planning/recherche', $this->validRequest())
            ->assertOk()
            ->assertExactJson($fixture);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === $url
            && $request->data() === ['filtre' => [
                'groupePermis' => 'A',
                'date' => '2026-09-29',
                'centreId' => self::CENTRE_ID,
            ]]);
    }

    #[DataProvider('planningErrors')]
    public function test_documented_planning_errors_are_mapped_without_exposing_provider_data(string $code, string $message): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/planning/recherche' => Http::response([
            'erreur' => $code,
            'access_token' => 'provider-private-token',
        ], 400)]);

        $response = $this->postJson('/api/rdvpermis/planning/recherche', $this->validRequest())
            ->assertBadRequest()
            ->assertExactJson(['message' => $message]);

        $this->assertStringNotContainsString('provider-private-token', $response->getContent());
    }

    public static function planningErrors(): array
    {
        return [
            'invalid parameter' => ['PARAMETRE_INVALIDE', 'Un paramètre envoyé à RdvPermis est invalide.'],
            'unknown centre' => ['CENTRE_INCONNU', 'Le centre sélectionné est inconnu de RdvPermis.'],
        ];
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_local_planning_filters_are_rejected_before_provider_call(array $payload, string $field): void
    {
        $this->signIn();
        Http::fake();

        $this->postJson('/api/rdvpermis/planning/recherche', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    public static function invalidRequests(): array
    {
        return [
            'permit group' => [[
                'groupePermis' => 'BA',
                'date' => '2026-09-29',
                'centreId' => self::CENTRE_ID,
            ], 'groupePermis'],
            'centre id' => [[
                'groupePermis' => 'A',
                'date' => '2026-09-29',
                'centreId' => 'not-a-uuid',
            ], 'centreId'],
            'date format' => [[
                'groupePermis' => 'A',
                'date' => '29/09/2026',
                'centreId' => self::CENTRE_ID,
            ], 'date'],
        ];
    }

    private function planningResult(string $status): array
    {
        return [
            'creneauDuPlanning' => [
                'centre' => ['id' => self::CENTRE_ID, 'nom' => 'MIRANDA CARS'],
                'numeroInspecteur' => '1357',
                'id' => '7aad1453-6560-4d50-8fae-90b5ba73fa37',
                'dateHeureDebut' => '2026-09-29T08:00:00',
                'dateHeureFin' => '2026-09-29T08:30:00',
                'dateHeurePublication' => '2026-09-20T08:30:00Z',
                'statutDeReservation' => $status,
                'groupePermis' => 'A',
                'typeEpreuvePratique' => 'PLATEAU',
            ],
        ];
    }

    private function validRequest(): array
    {
        return [
            'groupePermis' => 'A',
            'date' => '2026-09-29',
            'centreId' => self::CENTRE_ID,
        ];
    }

    private function signIn(): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindToken(User $user): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
