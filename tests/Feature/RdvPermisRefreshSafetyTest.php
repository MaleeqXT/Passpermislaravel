<?php

namespace Tests\Feature;

use App\Exceptions\RdvPermisApiException;
use App\Models\RdvPermisToken;
use App\Services\RdvPermis\TokenService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RdvPermisDatabaseTestCase;

class RdvPermisRefreshSafetyTest extends RdvPermisDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenSchema();
    }

    private function expired(): RdvPermisToken
    {
        $token = app(TokenService::class)->store($this->actor(), [
            'access_token' => 'old-access', 'refresh_token' => 'old-refresh',
            'expires_in' => 300, 'refresh_expires_in' => 3600,
        ]);
        $token->update(['access_token_expires_at' => now()->subMinute()]);

        return $token;
    }

    public function test_rotated_credentials_are_persisted_and_a_second_stale_request_reuses_them(): void
    {
        $stale = $this->expired();
        Http::fake(['https://auth.example.test/token' => Http::response([
            'access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 300,
        ])]);
        $service = app(TokenService::class);
        $this->assertTrue($service->refresh($stale) === 'new-access');
        $this->assertTrue($service->refresh($stale) === 'new-access');
        $this->assertTrue(RdvPermisToken::query()->sole()->refresh_token === 'new-refresh');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['refresh_token'] === 'old-refresh' && $request->isForm());
    }

    public function test_reread_under_lock_uses_a_token_already_refreshed_by_another_worker(): void
    {
        $stale = $this->expired();
        app(TokenService::class)->store($this->actor(), ['access_token' => 'other-worker-access', 'refresh_token' => 'rotated', 'expires_in' => 300]);
        $this->assertTrue(app(TokenService::class)->refresh($stale) === 'other-worker-access');
        Http::assertNothingSent();
    }

    public function test_lock_contention_returns_temporary_failure_without_sending_credentials(): void
    {
        $token = $this->expired();
        config()->set('rdvpermis.refresh_lock_wait_seconds', 0);
        $key = 'rdvpermis:refresh:'.hash('sha256', config('rdvpermis.token_url').'|'.config('rdvpermis.client_id').'|1');
        $lock = Cache::store('array')->lock($key, 30);
        $this->assertTrue($lock->get());
        try {
            app(TokenService::class)->refresh($token);
            $this->fail('A competing lock must prevent a second refresh.');
        } catch (RdvPermisApiException $exception) {
            $this->assertSame(503, $exception->responseStatus);
            $this->assertSame(2, $exception->retryAfter);
        } finally {
            $lock->release();
        }
        Http::assertNothingSent();
    }

    #[DataProvider('temporaryFailures')]
    public function test_temporary_refresh_failure_keeps_credentials_and_releases_the_lock(int $status): void
    {
        $token = $this->expired();
        Http::fake(fn () => $status === 0 ? throw new ConnectionException('private request credentials') : Http::response([], $status));
        for ($i = 0; $i < 2; $i++) {
            try {
                app(TokenService::class)->refresh($token);
                $this->fail('Temporary failure must be classified safely.');
            } catch (RdvPermisApiException $exception) {
                $this->assertSame(503, $exception->responseStatus);
                $this->assertStringNotContainsString('private request credentials', $exception->getMessage());
            }
        }
        $saved = RdvPermisToken::query()->sole();
        $this->assertSame('connected', $saved->status);
        $this->assertTrue($saved->refresh_token === 'old-refresh');
        Http::assertSentCount($status === 0 ? 0 : 2);
    }

    public static function temporaryFailures(): array
    {
        return [[0], [503], [401]];
    }

    public function test_invalid_grant_requires_reconnection_and_does_not_retry(): void
    {
        $token = $this->expired();
        Http::fake(['*' => Http::response(['error' => 'invalid_grant'], 400)]);
        for ($i = 0; $i < 2; $i++) {
            try {
                app(TokenService::class)->refresh($token);
                $this->fail('Invalid grant requires reconnection.');
            } catch (RdvPermisApiException $exception) {
                $this->assertSame(401, $exception->responseStatus);
            }
        }
        $this->assertSame('reconnect_required', $token->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_unknown_expiry_is_unverified_and_never_causes_endless_refresh(): void
    {
        $token = app(TokenService::class)->store($this->actor(), ['access_token' => 'unknown', 'refresh_token' => 'refresh']);
        $service = app(TokenService::class);
        $this->assertFalse($token->isAccessTokenValid());
        $this->assertSame('connection_unverified', $service->readiness($token));
        for ($i = 0; $i < 2; $i++) {
            try {
                $service->accessToken($this->actor());
                $this->fail('Unknown expiry must require reconnection.');
            } catch (RdvPermisApiException $exception) {
                $this->assertSame(401, $exception->responseStatus);
            }
        }
        Http::assertNothingSent();
    }

    public function test_success_without_expiry_stops_further_refresh_attempts(): void
    {
        $token = $this->expired();
        Http::fake(['*' => Http::response(['access_token' => 'unknown-expiry', 'refresh_token' => 'rotated'])]);
        for ($i = 0; $i < 2; $i++) {
            try {
                app(TokenService::class)->refresh($token);
                $this->fail('Missing expiry must not be silently trusted.');
            } catch (RdvPermisApiException $exception) {
                $this->assertSame(401, $exception->responseStatus);
            }
        }
        $this->assertSame('connection_unverified', $token->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_refresh_rate_limit_cools_down_without_changing_authentication_status(): void
    {
        $token = $this->expired();
        Http::fake(['*' => Http::response([], 429, ['Retry-After' => '30'])]);
        for ($i = 0; $i < 2; $i++) {
            try {
                app(TokenService::class)->refresh($token);
                $this->fail('Rate limited refresh should return retry information.');
            } catch (RdvPermisApiException $exception) {
                $this->assertSame(429, $exception->responseStatus);
                $this->assertGreaterThanOrEqual(29, $exception->retryAfter);
            }
        }
        $this->assertSame('connected', $token->fresh()->status);
        Http::assertSentCount(1);
    }
}
