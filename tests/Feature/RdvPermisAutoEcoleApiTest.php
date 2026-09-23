<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\RdvPermisToken;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisAutoEcoleApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The SPA's shared props query unrelated tables. Keep auth and role middleware enabled.
        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.debug', false);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.token_url', 'https://auth.example.test/token');
        config()->set('rdvpermis.client_id', 'test-client');
        config()->set('rdvpermis.client_secret', 'test-client-secret');
        config()->set('rdvpermis.timeout', 2);
        Log::spy();
        Http::preventStrayRequests();
    }

    public static function operations(): array
    {
        return [
            'current school' => ['/api/rdvpermis/current-school', '/api/v2/auto-ecole/moi'],
            'employees' => ['/api/rdvpermis/employees', '/api/v2/auto-ecole/employes'],
        ];
    }

    public static function allowedRoles(): iterable
    {
        foreach (self::operations() as $name => [$route, $path]) {
            foreach (['admin', 'super-admin', 'secretary'] as $role) {
                yield "$name / $role" => [$route, $path, $role];
            }
        }
    }

    public static function deniedRoles(): iterable
    {
        foreach (self::operations() as $name => [$route]) {
            foreach (['student', 'monitor', ''] as $role) {
                yield "$name / $role" => [$route, $role];
            }
        }
    }

    public static function upstreamErrors(): iterable
    {
        foreach (self::operations() as $name => [$route, $path]) {
            foreach ([401 => 401, 403 => 403, 404 => 404, 422 => 422, 429 => 429, 500 => 502, 503 => 502, 302 => 502] as $upstream => $local) {
                yield "$name / HTTP $upstream" => [$route, $path, $upstream, $local];
            }
        }
    }

    #[DataProvider('allowedRoles')]
    public function test_authorized_roles_call_the_existing_v2_operation(string $route, string $path, string $role): void
    {
        $user = $this->signIn($role);
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);

        // Opaque test data verifies forwarding; this is not an asserted Swagger response schema.
        $fixture = ['test_fixture' => ['value' => 'opaque provider response']];
        Http::fake(['https://api.example.test'.$path => Http::response($fixture)]);

        $this->getJson($route)->assertOk()->assertExactJson($fixture);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test'.$path
            && $request->method() === 'GET'
            && $request->body() === ''
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    #[DataProvider('operations')]
    public function test_guests_cannot_call_school_operations(string $route, string $path): void
    {
        Http::fake();
        $this->getJson($route)->assertUnauthorized();
        Http::assertNothingSent();
    }

    #[DataProvider('deniedRoles')]
    public function test_non_administrative_roles_are_rejected_before_any_provider_call(string $route, string $role): void
    {
        $this->signIn($role);
        Http::fake();
        $this->getJson($route)->assertForbidden();
        Http::assertNothingSent();
    }

    #[DataProvider('upstreamErrors')]
    public function test_upstream_errors_are_mapped_without_exposing_provider_secrets(string $route, string $path, int $upstream, int $local): void
    {
        $user = $this->signIn('secretary');
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        if ($upstream === 401) {
            $tokens->shouldReceive('markNeedsReauthentication')->once()->with($user);
        } else {
            $tokens->shouldNotReceive('markNeedsReauthentication');
        }
        $this->app->instance(TokenService::class, $tokens);
        Http::fake(['https://api.example.test'.$path => Http::response([
            'debug' => 'provider-private-details',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'client_secret' => 'test-client-secret',
            'code' => 'fake-authorization-code',
        ], $upstream)]);

        $response = $this->getJson($route)->assertStatus($local)->assertJsonStructure(['message']);
        foreach (['provider-private-details', 'fake-access-token', 'fake-refresh-token', 'test-client-secret', 'fake-authorization-code'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
        Http::assertSentCount(1);
    }

    #[DataProvider('operations')]
    public function test_expired_access_tokens_are_refreshed_before_the_api_call(string $route, string $path): void
    {
        $service = $this->bindExpiredToken($this->signIn('admin'));
        Http::fake([
            'https://auth.example.test/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'rotated-refresh-token',
                'expires_in' => 300,
                'scope' => 'rdvpermis',
            ]),
            'https://api.example.test'.$path => Http::response(['test_fixture' => true]),
        ]);

        $this->getJson($route)->assertOk()->assertExactJson(['test_fixture' => true]);
        $this->assertSame('rotated-refresh-token', $service->token->refresh_token);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://auth.example.test/token'
            && $request->method() === 'POST' && $request->isForm()
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'old-refresh-token'
            && $request['client_id'] === 'test-client'
            && $request['client_secret'] === 'test-client-secret');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test'.$path
            && $request->hasHeader('Authorization', 'Bearer new-access-token'));
    }

    #[DataProvider('operations')]
    public function test_rejected_refresh_requires_reconnection_and_prevents_the_api_call(string $route, string $path): void
    {
        $service = $this->bindExpiredToken($this->signIn('admin'));
        Http::fake(['https://auth.example.test/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'private refresh token information',
        ], 400)]);

        $response = $this->getJson($route)->assertUnauthorized();
        $this->assertSame('reconnect_required', $service->token->status);
        $this->assertStringNotContainsString('private refresh token information', $response->getContent());
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->url() === 'https://api.example.test'.$path);
    }

    private function signIn(string $role): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect($role === '' ? [] : [new Role(['name' => $role, 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindExpiredToken(User $user): TokenService
    {
        $token = new class extends RdvPermisToken
        {
            public function update(array $attributes = [], array $options = []): bool
            {
                $this->forceFill($attributes);

                return true;
            }
        };
        $token->user_id = $user->id;
        $token->status = 'connected';
        $token->access_token = 'old-access-token';
        $token->access_token_expires_at = now()->subMinute();
        $token->refresh_token = 'old-refresh-token';
        $token->refresh_expires_at = now()->addHour();
        $token->setRelation('user', $user);

        // Only persistence is replaced. accessToken(), refresh() and expiry handling run unchanged.
        $service = new class($token) extends TokenService
        {
            public function __construct(public RdvPermisToken $token) {}

            public function forUser(User $user): ?RdvPermisToken
            {
                return $this->token;
            }

            public function store(User $user, array $payload): RdvPermisToken
            {
                $this->token->access_token = $payload['access_token'];
                $this->token->refresh_token = $payload['refresh_token'];
                $this->token->access_token_expires_at = now()->addSeconds($payload['expires_in']);

                return $this->token;
            }
        };
        $this->app->instance(TokenService::class, $service);

        return $service;
    }
}
