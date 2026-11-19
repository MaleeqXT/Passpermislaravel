<?php

namespace Tests\Feature;

use App\Models\RdvPermisOAuthState;
use App\Models\User;
use App\Services\RdvPermis\OAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Local development connects through the deployed (staging2) callback:
 * local marks its state "local.", staging2 forwards that callback to the
 * configured loopback URL without using the code, and local exchanges it.
 */
class RdvPermisLocalHandoffTest extends TestCase
{
    private const DEPLOYED_CALLBACK = 'https://staging2.passpermisfacile.fr/api/rdvpermis/callback';

    private const LOCAL_CALLBACK = 'http://localhost:8000/api/rdvpermis/callback';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'rdvpermis_handoff_test');
        config()->set('database.connections.rdvpermis_handoff_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('rdvpermis_handoff_test');
        DB::setDefaultConnection('rdvpermis_handoff_test');
        (require database_path('migrations/2026_09_24_000000_create_rdvpermis_oauth_states_table.php'))->up();

        Log::spy();
        Http::preventStrayRequests();
        config()->set('rdvpermis.redirect_uri', self::DEPLOYED_CALLBACK);
        config()->set('rdvpermis.client_id', 'fixture-client');
        config()->set('rdvpermis.frontend_callback_url', 'https://reactfast.passpermisfacile.fr/');
    }

    protected function tearDown(): void
    {
        DB::purge('rdvpermis_handoff_test');
        parent::tearDown();
    }

    public function test_deployed_callback_forwards_a_local_state_to_the_loopback_callback_without_using_the_code(): void
    {
        config()->set('rdvpermis.local_handoff_url', self::LOCAL_CALLBACK);

        $response = $this->get('/api/rdvpermis/callback?code=one-time-code&state=local.abc123&session_state=ignored');

        $response->assertRedirect(self::LOCAL_CALLBACK.'?code=one-time-code&state=local.abc123');
        Http::assertNothingSent();
        $this->assertSame(0, RdvPermisOAuthState::query()->count());
    }

    public function test_provider_errors_for_a_local_state_are_forwarded_too(): void
    {
        config()->set('rdvpermis.local_handoff_url', self::LOCAL_CALLBACK);

        $this->get('/api/rdvpermis/callback?error=access_denied&state=local.abc123')
            ->assertRedirect(self::LOCAL_CALLBACK.'?state=local.abc123&error=access_denied');
        Http::assertNothingSent();
    }

    public function test_a_non_loopback_handoff_url_is_never_used_as_a_redirect(): void
    {
        config()->set('rdvpermis.local_handoff_url', 'https://attacker.example/callback');
        $oauth = Mockery::mock(OAuthService::class)->makePartial();
        $oauth->shouldReceive('exchangeAuthorizationCode')->once()->with('one-time-code', 'local.abc123')
            ->andThrow(new \App\Exceptions\RdvPermisOAuthException('state_validation', 'Unknown state.'));
        $this->app->instance(OAuthService::class, $oauth);

        $response = $this->get('/api/rdvpermis/callback?code=one-time-code&state=local.abc123');

        $response->assertRedirect('https://reactfast.passpermisfacile.fr/?rdvpermis=error');
        $this->assertStringNotContainsString('attacker.example', (string) $response->headers->get('Location'));
    }

    public function test_deployed_states_are_still_handled_by_the_deployment(): void
    {
        config()->set('rdvpermis.local_handoff_url', self::LOCAL_CALLBACK);
        $oauth = Mockery::mock(OAuthService::class)->makePartial();
        $oauth->shouldReceive('exchangeAuthorizationCode')->once()->with('one-time-code', 'deployed-state')->andReturn(new User);
        $this->app->instance(OAuthService::class, $oauth);

        $this->get('/api/rdvpermis/callback?code=one-time-code&state=deployed-state')
            ->assertRedirect('https://reactfast.passpermisfacile.fr/?rdvpermis=connected');
    }

    public function test_without_a_handoff_url_a_local_state_is_not_forwarded(): void
    {
        config()->set('rdvpermis.local_handoff_url', null);

        $this->assertNull(app(OAuthService::class)->localHandoffTarget('local.abc123'));
    }

    public function test_local_process_marks_its_state_and_keeps_the_deployed_redirect_uri(): void
    {
        $this->app['env'] = 'local';
        config()->set('app.url', 'http://localhost');
        config()->set('rdvpermis.local_handoff', true);
        $oauth = app(OAuthService::class);

        $this->assertTrue($oauth->localHandoffEnabled());
        $this->assertNull($oauth->localCallbackConfigurationError());

        $user = new User;
        $user->id = 1;
        parse_str((string) parse_url($oauth->authorizationUrl($user), PHP_URL_QUERY), $query);

        $this->assertSame(self::DEPLOYED_CALLBACK, $query['redirect_uri']);
        $this->assertStringStartsWith(OAuthService::LOCAL_STATE_PREFIX, $query['state']);
        $this->assertSame(1, RdvPermisOAuthState::query()->count());
        // The local process handles its own forwarded callback instead of forwarding again.
        config()->set('rdvpermis.local_handoff_url', self::LOCAL_CALLBACK);
        $this->assertNull($oauth->localHandoffTarget($query['state']));
    }

    public function test_local_process_without_handoff_still_reports_the_callback_split(): void
    {
        $this->app['env'] = 'local';
        config()->set('app.url', 'http://localhost');
        config()->set('rdvpermis.local_handoff', false);

        $this->assertNotNull(app(OAuthService::class)->localCallbackConfigurationError());
    }

    public function test_handoff_is_ignored_outside_the_local_environment(): void
    {
        $this->app['env'] = 'production';
        config()->set('app.url', 'http://localhost');
        config()->set('rdvpermis.local_handoff', true);

        $this->assertFalse(app(OAuthService::class)->localHandoffEnabled());
    }
}
