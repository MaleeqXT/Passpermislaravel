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
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        Http::preventStrayRequests();
    }

    public function test_it_forwards_an_opaque_schema_until_current_swagger_fields_are_captured(): void
    {
        $user = $this->signIn();
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
        $payload = ['opaqueCurrentSwaggerFixture' => ['value' => 'preserved']];
        $response = ['data' => ['opaque' => true]];
        $url = 'https://api.example.test/api/v2/auto-ecole/candidats-groupe-lourd/recherche';
        Http::fake([$url => Http::response($response)]);

        $this->postJson('/api/rdvpermis/candidats-groupe-lourd/recherche', $payload)
            ->assertOk()->assertExactJson($response);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === $url && $request->data() === $payload);
    }

    private function signIn(): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }
}
