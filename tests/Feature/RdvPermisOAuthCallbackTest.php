<?php

namespace Tests\Feature;

use App\Exceptions\RdvPermisOAuthException;
use App\Models\User;
use App\Services\RdvPermis\OAuthService;
use Mockery;
use Tests\TestCase;

class RdvPermisOAuthCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('rdvpermis.frontend_callback_url', 'https://reactfast.passpermisfacile.fr/');
    }

    public function test_successful_callback_redirects_to_the_frontend_connected_page(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeAuthorizationCode')
            ->once()
            ->with('one-time-code', 'valid-state')
            ->andReturn(new User);
        $this->app->instance(OAuthService::class, $oauth);

        $this->get('/api/rdvpermis/callback?code=one-time-code&state=valid-state')
            ->assertRedirect('https://reactfast.passpermisfacile.fr/?rdvpermis=connected');
    }

    public function test_failed_callback_redirects_to_the_frontend_error_page_without_returning_the_code(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeAuthorizationCode')
            ->once()
            ->with('one-time-code', 'expired-state')
            ->andThrow(new RdvPermisOAuthException('state_validation', 'The request expired.'));
        $this->app->instance(OAuthService::class, $oauth);

        $response = $this->get('/api/rdvpermis/callback?code=one-time-code&state=expired-state');

        $response->assertRedirect('https://reactfast.passpermisfacile.fr/?rdvpermis=error');
        $this->assertStringNotContainsString('one-time-code', $response->getContent());
    }
}
