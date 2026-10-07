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
}
