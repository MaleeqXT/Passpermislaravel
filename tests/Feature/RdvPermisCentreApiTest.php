<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisCentreApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
    }

    public function test_centres_and_favorites_use_the_verified_v2_contracts(): void
    {
        $user = $this->signIn();
        $this->bindToken($user, 4);
        $centre = ['id' => 'centre-1', 'nom' => 'Centre test', 'estFerme' => false];
        $favorites = [['groupePermis' => 'B', 'centreId' => 'centre-1', 'priorite' => 0]];
        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/centres/recherche' => Http::response([$centre]),
            'https://api.example.test/api/v2/auto-ecole/centres/centre-1' => Http::response($centre),
            'https://api.example.test/api/v2/auto-ecole/centres-favoris' => Http::sequence()->push($favorites)->push(['groupePermis' => 'B', 'centresFavorisInput' => [['centreId' => 'centre-1', 'priorite' => 0]]]),
        ]);

        $this->postJson('/api/rdvpermis/centres/recherche', [
            'codeDepartement' => '091', 'groupePermis' => 'B', 'estFerme' => false,
        ])->assertOk()->assertExactJson([$centre]);
        $this->getJson('/api/rdvpermis/centres/centre-1')->assertOk()->assertExactJson($centre);
        $this->getJson('/api/rdvpermis/centres-favoris')->assertOk()->assertExactJson($favorites);
        $this->postJson('/api/rdvpermis/centres-favoris', [
            'groupePermis' => 'B', 'centresFavorisInput' => [['centreId' => 'centre-1', 'priorite' => 0]],
        ])->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/api/v2/auto-ecole/centres/recherche'
            && $request->data() === ['filtre' => ['codeDepartement' => '091', 'groupePermis' => 'B', 'estFerme' => false]]);
    }

    public function test_invalid_centre_input_does_not_call_rdvpermis(): void
    {
        $this->signIn();
        Http::fake();

        $this->postJson('/api/rdvpermis/centres/recherche', ['codeDepartement' => '091', 'groupePermis' => 'BA'])
            ->assertUnprocessable()->assertJsonValidationErrors('groupePermis');
        $this->postJson('/api/rdvpermis/centres-favoris', ['groupePermis' => 'B', 'centresFavorisInput' => [['priorite' => 3]]])
            ->assertUnprocessable()->assertJsonValidationErrors('centresFavorisInput.0.priorite');
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

    private function bindToken(User $user, int $times): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->times($times)->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
