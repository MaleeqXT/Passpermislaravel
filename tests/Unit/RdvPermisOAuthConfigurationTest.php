<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RdvPermis\OAuthService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RdvPermisOAuthConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_it_builds_the_official_recette1_authorization_code_url_without_a_network_call(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/auth');
        config()->set('rdvpermis.client_id', 'ef4a4dd3-ff91-4ff1-9cdf-961ad464c655');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/api/rdvpermis/callback');
        config()->set('rdvpermis.scopes', ['rdvpermis', 'livret_numerique:read', 'livret_numerique:write', 'offline_access']);

        $user = new User;
        $user->id = 1;

        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame(config('rdvpermis.client_id'), $query['client_id']);
        $this->assertSame(config('rdvpermis.redirect_uri'), $query['redirect_uri']);
        $this->assertSame('rdvpermis livret_numerique:read livret_numerique:write offline_access', $query['scope']);
        $this->assertNotEmpty($query['state']);
        $this->assertSame(1, Cache::get('rdvpermis:oauth-state:'.$query['state']));
    }

    public function test_it_rejects_an_unknown_or_already_consumed_state(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('demande de connexion a expiré');

        app(OAuthService::class)->consumeState('unknown-state');
    }

    public function test_it_exchanges_the_code_as_form_data_without_exposing_tokens(): void
    {
        config()->set('rdvpermis.token_url', 'https://recette.example.test/token');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.client_secret', 'server-side-secret');
        config()->set('rdvpermis.redirect_uri', 'https://app.example.test/api/rdvpermis/callback');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://recette.example.test/token' => Http::response([
                'access_token' => 'sensitive-access-token',
                'refresh_token' => 'sensitive-refresh-token',
                'expires_in' => 300,
                'refresh_expires_in' => 1800,
                'scope' => 'rdvpermis offline_access',
            ]),
        ]);

        $user = new User;
        $user->id = 42;

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('store')
            ->once()
            ->with($user, Mockery::on(fn (array $payload) => $payload['access_token'] === 'sensitive-access-token'
                && $payload['refresh_token'] === 'sensitive-refresh-token'));

        $service = new class($tokens, $user) extends OAuthService
        {
            public function __construct(TokenService $tokens, private readonly User $testUser)
            {
                parent::__construct($tokens);
            }

            public function consumeState(string $state): User
            {
                return $this->testUser;
            }
        };

        $this->assertSame($user, $service->exchangeAuthorizationCode('one-time-code', 'valid-state'));

        Http::assertSent(fn ($request) => $request->isForm()
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'one-time-code'
            && $request['client_id'] === 'client-id'
            && $request['client_secret'] === 'server-side-secret'
            && $request['redirect_uri'] === 'https://app.example.test/api/rdvpermis/callback');
    }

    public function test_it_refuses_token_exchange_when_the_ministry_client_secret_is_not_configured(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://example.test/authorize');
        config()->set('rdvpermis.client_id', 'public-client-id');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/callback');
        config()->set('rdvpermis.token_url', 'https://example.test/token');
        config()->set('rdvpermis.client_secret', null);

        $user = new User;
        $user->id = 2;
        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration RECETTE1 RdvPermis est incomplète');

        app(OAuthService::class)->exchangeAuthorizationCode('unused-code', $query['state']);
    }
}
