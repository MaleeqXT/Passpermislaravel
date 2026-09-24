<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RdvPermis\OAuthService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RdvPermisOAuthConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        config()->set('database.default', 'rdvpermis_oauth_test');
        config()->set('database.connections.rdvpermis_oauth_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('rdvpermis_oauth_test');
        DB::setDefaultConnection('rdvpermis_oauth_test');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('status')->default(1);
        });
        (require database_path('migrations/2026_09_24_000000_create_rdvpermis_oauth_states_table.php'))->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rdvpermis_oauth_states');
        Schema::dropIfExists('users');
        DB::purge('rdvpermis_oauth_test');

        parent::tearDown();
    }

    public function test_it_builds_the_official_recette1_authorization_code_url_without_a_network_call(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://recette.moncompte.permisdeconduire.gouv.fr/auth/realms/formation/protocol/openid-connect/auth');
        config()->set('rdvpermis.client_id', 'ef4a4dd3-ff91-4ff1-9cdf-961ad464c655');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/api/rdvpermis/callback');
        config()->set('rdvpermis.scopes', ['rdvpermis', 'livret_numerique:read', 'livret_numerique:write', 'offline_access']);

        $user = $this->persistedUser(1);

        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame(config('rdvpermis.client_id'), $query['client_id']);
        $this->assertSame(config('rdvpermis.redirect_uri'), $query['redirect_uri']);
        $this->assertSame('rdvpermis livret_numerique:read livret_numerique:write offline_access', $query['scope']);
        $this->assertNotEmpty($query['state']);
        $this->assertDatabaseHas('rdvpermis_oauth_states', [
            'user_id' => 1,
            'state_hash' => hash('sha256', $query['state']),
            'consumed_at' => null,
        ]);
        $this->assertDatabaseMissing('rdvpermis_oauth_states', ['state_hash' => $query['state']]);
    }

    public function test_it_rejects_an_unknown_or_already_consumed_state(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('demande de connexion a expiré');

        app(OAuthService::class)->consumeState('unknown-state');
    }

    public function test_it_consumes_a_fresh_state_and_resolves_the_original_user(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://example.test/authorize');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/callback');
        $user = $this->persistedUser(34);

        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $resolvedUser = app(OAuthService::class)->consumeState($query['state']);

        $this->assertSame(34, $resolvedUser->getKey());
        $this->assertDatabaseHas('rdvpermis_oauth_states', [
            'user_id' => 34,
            'state_hash' => hash('sha256', $query['state']),
        ]);
        $this->assertNotNull(DB::table('rdvpermis_oauth_states')
            ->where('state_hash', hash('sha256', $query['state']))
            ->value('consumed_at'));
    }

    public function test_it_rejects_expired_and_reused_states(): void
    {
        $user = $this->persistedUser(35);
        $expiredState = bin2hex(random_bytes(32));
        DB::table('rdvpermis_oauth_states')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'state_hash' => hash('sha256', $expiredState),
            'expires_at' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(OAuthService::class)->consumeState($expiredState);
            $this->fail('An expired state must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('demande de connexion a expiré', $exception->getMessage());
        }

        config()->set('rdvpermis.authorization_url', 'https://example.test/authorize');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/callback');
        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        app(OAuthService::class)->consumeState($query['state']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('demande de connexion a expiré');
        app(OAuthService::class)->consumeState($query['state']);
    }

    public function test_it_exchanges_the_code_as_form_data_without_exposing_tokens(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://recette.example.test/authorize');
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

        $user = $this->persistedUser(42);

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('store')
            ->once()
            ->with(Mockery::on(fn (User $resolvedUser) => $resolvedUser->getKey() === $user->getKey()), Mockery::on(fn (array $payload) => $payload['access_token'] === 'sensitive-access-token'
                && $payload['refresh_token'] === 'sensitive-refresh-token'));

        $service = new OAuthService($tokens);
        $url = $service->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame($user->getKey(), $service->exchangeAuthorizationCode('one-time-code', $query['state'])->getKey());

        Http::assertSent(fn ($request) => $request->isForm()
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'one-time-code'
            && $request['client_id'] === 'client-id'
            && $request['client_secret'] === 'server-side-secret'
            && $request['redirect_uri'] === 'https://app.example.test/api/rdvpermis/callback');
    }

    public function test_it_never_exchanges_a_code_before_state_validation_succeeds(): void
    {
        config()->set('rdvpermis.token_url', 'https://recette.example.test/token');
        config()->set('rdvpermis.client_id', 'client-id');
        config()->set('rdvpermis.client_secret', 'server-side-secret');
        config()->set('rdvpermis.redirect_uri', 'https://app.example.test/api/rdvpermis/callback');
        Http::fake();

        $this->expectException(RuntimeException::class);

        try {
            app(OAuthService::class)->exchangeAuthorizationCode('one-time-code', 'unknown-state');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_refuses_token_exchange_when_the_ministry_client_secret_is_not_configured(): void
    {
        config()->set('rdvpermis.authorization_url', 'https://example.test/authorize');
        config()->set('rdvpermis.client_id', 'public-client-id');
        config()->set('rdvpermis.redirect_uri', 'https://example.test/callback');
        config()->set('rdvpermis.token_url', 'https://example.test/token');
        config()->set('rdvpermis.client_secret', null);

        $user = $this->persistedUser(2);
        $url = app(OAuthService::class)->authorizationUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration RECETTE1 RdvPermis est incomplète');

        app(OAuthService::class)->exchangeAuthorizationCode('unused-code', $query['state']);
    }

    private function persistedUser(int $id): User
    {
        DB::table('users')->insert(['id' => $id]);

        return User::query()->without(['monitor', 'student', 'secretary'])->findOrFail($id);
    }
}
