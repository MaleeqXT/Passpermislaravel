<?php

namespace Tests\Unit;

use App\Models\RdvPermisToken;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class RdvPermisTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
        Http::preventStrayRequests();
    }

    public function test_sensitive_token_attributes_are_encrypted_by_the_model(): void
    {
        $token = new RdvPermisToken;
        $token->access_token = 'plain-access-token';
        $token->refresh_token = 'plain-refresh-token';
        $token->scopes = ['rdvpermis', 'offline_access'];

        $attributes = $token->getAttributes();
        $this->assertNotEmpty($attributes['access_token']);
        $this->assertNotEmpty($attributes['refresh_token']);
        $this->assertNotSame('plain-access-token', $attributes['access_token']);
        $this->assertNotSame('plain-refresh-token', $attributes['refresh_token']);
        $this->assertSame('plain-access-token', decrypt($attributes['access_token'], false));
        $this->assertSame('plain-refresh-token', decrypt($attributes['refresh_token'], false));
        $this->assertSame('plain-access-token', $token->access_token);
        $this->assertSame('plain-refresh-token', $token->refresh_token);
        $this->assertSame(['rdvpermis', 'offline_access'], $token->scopes);
        $this->assertArrayNotHasKey('access_token', $token->toArray());
        $this->assertArrayNotHasKey('refresh_token', $token->toArray());
    }

    public function test_a_valid_access_token_without_a_refresh_token_is_used_directly(): void
    {
        $user = new User;
        $user->id = 10;

        $token = new RdvPermisToken;
        $token->user_id = $user->id;
        $token->status = 'connected';
        $token->access_token = 'fresh-imported-access-token';
        $token->access_token_expires_at = now()->addMinutes(5);
        $token->refresh_token = null;

        $service = new class($token) extends TokenService
        {
            public function __construct(private readonly RdvPermisToken $token) {}

            public function forUser(User $user): ?RdvPermisToken
            {
                return $this->token;
            }
        };

        $this->assertSame('fresh-imported-access-token', $service->accessToken($user));
        $this->assertSame('connected', $token->status);
        Http::assertNothingSent();
    }

    public function test_an_expired_access_token_without_a_refresh_token_requires_a_new_import(): void
    {
        $user = new User;
        $user->id = 11;

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
        $token->access_token = 'expired-imported-access-token';
        $token->access_token_expires_at = now()->subSecond();
        $token->refresh_token = null;

        $service = new class($token) extends TokenService
        {
            public function __construct(private readonly RdvPermisToken $token) {}

            public function forUser(User $user): ?RdvPermisToken
            {
                return $this->token;
            }
        };

        try {
            $service->accessToken($user);
            $this->fail('An expired imported access token must not be reused.');
        } catch (RuntimeException $exception) {
            $this->assertSame('reconnect_required', $token->status);
            $this->assertStringContainsString('a expiré', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_an_expired_access_token_uses_the_existing_refresh_token_flow(): void
    {
        config()->set('rdvpermis.token_url', 'https://auth.example.test/token');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.client_secret', 'server-side-secret');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://auth.example.test/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'refresh_token' => 'rotated-refresh-token',
                'expires_in' => 300,
            ]),
        ]);

        $user = new User;
        $user->id = 12;

        $token = new RdvPermisToken;
        $token->user_id = $user->id;
        $token->status = 'connected';
        $token->access_token = 'expired-access-token';
        $token->access_token_expires_at = now()->subSecond();
        $token->refresh_token = 'old-refresh-token';
        $token->refresh_expires_at = now()->addMinute();
        $token->setRelation('user', $user);

        $service = new class($token) extends TokenService
        {
            public function __construct(private readonly RdvPermisToken $token) {}

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

        $this->assertSame('refreshed-access-token', $service->accessToken($user));
        $this->assertSame('rotated-refresh-token', $token->refresh_token);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://auth.example.test/token'
            && $request->method() === 'POST'
            && $request->isForm()
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'old-refresh-token');
    }

    public function test_refresh_uses_and_preserves_the_rotated_refresh_token_payload(): void
    {
        config()->set('rdvpermis.token_url', 'https://auth.example.test/token');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.client_secret', 'server-side-secret');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://auth.example.test/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'rotated-refresh-token',
                'expires_in' => 300,
            ]),
        ]);

        $user = new User;
        $user->id = 12;

        $token = new RdvPermisToken;
        $token->user_id = 12;
        $token->refresh_token = 'old-refresh-token';
        $token->setRelation('user', $user);

        $service = new class extends TokenService
        {
            public array $storedPayload = [];

            public function store(User $user, array $payload): RdvPermisToken
            {
                $this->storedPayload = $payload;

                $token = new RdvPermisToken;
                $token->access_token = $payload['access_token'];
                $token->refresh_token = $payload['refresh_token'] ?? null;

                return $token;
            }
        };

        $this->assertSame('new-access-token', $service->refresh($token));
        $this->assertSame('rotated-refresh-token', $service->storedPayload['refresh_token']);

        Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'old-refresh-token'
            && $request['client_secret'] === 'server-side-secret');
    }

    public function test_a_rejected_refresh_marks_the_connection_as_reconnect_required(): void
    {
        config()->set('rdvpermis.token_url', 'https://auth.example.test/token');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.client_secret', 'server-side-secret');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://auth.example.test/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $user = new User;
        $user->id = 13;

        $token = new class extends RdvPermisToken
        {
            public function update(array $attributes = [], array $options = []): bool
            {
                $this->forceFill($attributes);

                return true;
            }
        };
        $token->user_id = 13;
        $token->refresh_token = 'expired-refresh-token';
        $token->refresh_expires_at = now()->addMinute();
        $token->setRelation('user', $user);

        try {
            app(TokenService::class)->refresh($token);
            $this->fail('A rejected refresh should require a new OAuth connection.');
        } catch (RuntimeException $exception) {
            $this->assertSame('reconnect_required', $token->status);
            $this->assertStringContainsString('reconnecter', $exception->getMessage());
        }
    }
}
