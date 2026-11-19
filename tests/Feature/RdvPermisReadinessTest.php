<?php

namespace Tests\Feature;

use App\Models\RdvPermisToken;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Support\RdvPermisDatabaseTestCase;

class RdvPermisReadinessTest extends RdvPermisDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenSchema();
        config()->set('app.url', 'https://backend.example.test');
        config()->set('rdvpermis.authorization_url', 'https://auth.example.test/auth');
        config()->set('rdvpermis.redirect_uri', 'https://backend.example.test/api/rdvpermis/callback');
        config()->set('rdvpermis.frontend_callback_url', 'https://frontend.example.test');
        $user = $this->actor();
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);
    }

    #[DataProvider('states')]
    public function test_status_reflects_local_usability_without_provider_requests(string $state, string $expected): void
    {
        if ($state !== 'absent') {
            $token = app(TokenService::class)->store($this->actor(), [
                'access_token' => 'fixture-access', 'expires_in' => 300,
                'refresh_token' => $state === 'expired-refreshable' ? 'fixture-refresh' : null,
                'refresh_expires_in' => 3600,
            ]);
            if (str_starts_with($state, 'expired')) {
                $token->update(['access_token_expires_at' => now()->subMinute()]);
            } elseif ($state === 'unknown') {
                $token->update(['access_token_expires_at' => null]);
            } elseif ($state === 'rejected') {
                $token->update(['status' => 'reconnect_required', 'last_error' => 'private database diagnostic']);
            } elseif ($state === 'corrupt') {
                DB::table('rdvpermis_tokens')->update(['access_token' => 'unreadable']);
            } elseif ($state === 'misconfigured') {
                config()->set('rdvpermis.client_secret', null);
            }
        }
        $response = $this->getJson('/api/rdvpermis/status')->assertOk()
            ->assertJsonPath('data.status', $expected)->assertJsonPath('data.connected', $expected === 'connected');
        $this->assertStringNotContainsString('fixture-access', $response->getContent());
        $this->assertStringNotContainsString('private database diagnostic', $response->getContent());
        Http::assertNothingSent();
    }

    public static function states(): array
    {
        return [
            ['absent', 'not_connected'], ['valid', 'connected'],
            ['expired-refreshable', 'connection_unverified'], ['expired-no-refresh', 'reconnect_required'],
            ['unknown', 'connection_unverified'], ['rejected', 'reconnect_required'],
            ['corrupt', 'reconnect_required'], ['misconfigured', 'connection_unverified'],
        ];
    }

    public function test_missing_expiry_response_stays_unverified_after_reloading_the_database(): void
    {
        app(TokenService::class)->store($this->actor(), ['access_token' => 'fixture']);
        $this->assertSame('connection_unverified', RdvPermisToken::query()->sole()->status);
        $this->getJson('/api/rdvpermis/status')->assertOk()->assertJsonPath('data.connected', false);
        Http::assertNothingSent();
    }

    public function test_local_expired_unusable_token_is_cleared_and_reported_as_disconnected(): void
    {
        config()->set('app.url', 'http://localhost');
        config()->set('rdvpermis.redirect_uri', 'https://backend.example.test/api/rdvpermis/callback');
        $token = app(TokenService::class)->store($this->actor(), [
            'access_token' => 'fixture-access',
            'expires_in' => 1,
        ]);
        $token->update(['access_token_expires_at' => now()->subMinute()]);

        $response = $this->getJson('/api/rdvpermis/status')->assertOk()
            ->assertJsonPath('data.status', 'not_connected')
            ->assertJsonPath('data.connected', false)
            ->assertJsonPath('data.configurationComplete', true)
            ->assertJsonPath('data.localOAuthUnavailable', true)
            ->assertJsonPath('data.stale_token_cleared', true)
            ->assertJsonPath('data.authorizedEnvironmentUrl', 'https://frontend.example.test/rdvpermis')
            ->assertJsonPath('data.message', 'La connexion OAuth RdvPermis doit être effectuée depuis l’environnement disposant du callback RECETTE autorisé.');

        $this->assertDatabaseMissing('rdvpermis_tokens', ['user_id' => $this->actor()->getKey()]);
        $this->assertStringNotContainsString('fixture-access', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_deployed_expired_connection_is_not_deleted_or_marked_as_local_oauth_unavailable(): void
    {
        $token = app(TokenService::class)->store($this->actor(), [
            'access_token' => 'fixture-access',
            'expires_in' => 1,
        ]);
        $token->update(['access_token_expires_at' => now()->subMinute()]);

        $this->getJson('/api/rdvpermis/status')->assertOk()
            ->assertJsonPath('data.status', 'reconnect_required')
            ->assertJsonPath('data.localOAuthUnavailable', false)
            ->assertJsonPath('data.authorizedEnvironmentUrl', null)
            ->assertJsonPath('data.stale_token_cleared', false);

        $this->assertDatabaseHas('rdvpermis_tokens', ['user_id' => $this->actor()->getKey()]);
        Http::assertNothingSent();
    }

    public function test_local_connect_is_rejected_before_creating_state_when_callback_targets_another_deployment(): void
    {
        config()->set('app.url', 'http://localhost');
        config()->set('rdvpermis.redirect_uri', 'https://staging.example.test/api/rdvpermis/callback');

        $this->postJson('/api/rdvpermis/connect')
            ->assertConflict()
            ->assertJsonPath('message', 'La connexion RdvPermis locale ne peut pas être finalisée : le callback OAuth est configuré vers un autre déploiement. Utilisez un callback localhost/HTTPS enregistré pour cette base locale, ou connectez-vous depuis le déploiement qui possède ce callback.');

        Http::assertNothingSent();
    }
}
