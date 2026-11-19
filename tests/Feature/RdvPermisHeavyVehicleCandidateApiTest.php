<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisHeavyVehicleCandidateApiTest extends TestCase
{
    private const URL = 'https://api.example.test/api/v2/auto-ecole/candidats-groupe-lourd/recherche';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        Http::preventStrayRequests();
    }

    public function test_it_sends_the_swagger_search_body_with_validated_fields_only(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $response = ['data' => [], 'pagination' => ['pageCourante' => 2]];
        Http::fake([self::URL => Http::response($response)]);

        $this->postJson('/api/rdvpermis/candidats-groupe-lourd/recherche', [
            'filtre' => [
                'groupePermis' => 'CE',
                'nom' => ['query' => 'Dupon', 'match' => 'PARTIAL', 'extra' => 'dropped'],
                'unknown' => 'dropped',
            ],
            'page' => 2,
            'parPage' => 50,
            'opaque' => 'dropped',
        ])->assertOk()->assertExactJson($response);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === self::URL
            && $request->data() === [
                'filtre' => ['groupePermis' => 'CE', 'nom' => ['query' => 'Dupon', 'match' => 'PARTIAL']],
                'page' => 2,
                'parPage' => 50,
            ]);
    }

    public function test_an_empty_search_sends_a_json_object_for_the_full_list(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake([self::URL => Http::response(['data' => []])]);

        $this->postJson('/api/rdvpermis/candidats-groupe-lourd/recherche', [])->assertOk();

        Http::assertSent(fn ($request) => $request->url() === self::URL && $request->body() === '{}');
    }

    public function test_invalid_search_input_does_not_call_rdvpermis(): void
    {
        $this->signIn();
        Http::fake();

        foreach ([
            ['filtre' => ['nom' => ['query' => 'Dupon', 'match' => 'PARTIAL']]],
            ['filtre' => ['groupePermis' => 'B']],
            ['filtre' => ['groupePermis' => 'CE', 'nom' => ['query' => 'Dupon', 'match' => 'FUZZY']]],
            ['filtre' => ['groupePermis' => 'CE', 'numeroDossier' => ['match' => 'EXACT']]],
            ['parPage' => 101],
            ['page' => 0],
        ] as $payload) {
            $this->postJson('/api/rdvpermis/candidats-groupe-lourd/recherche', $payload)->assertUnprocessable();
        }

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

    private function bindToken(User $user): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
