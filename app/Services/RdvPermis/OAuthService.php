<?php

namespace App\Services\RdvPermis;

use App\Exceptions\RdvPermisOAuthException;
use App\Models\RdvPermisOAuthState;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OAuthService
{
    public function __construct(private readonly TokenService $tokens) {}

    public function authorizationUrl(User $user): string
    {
        $this->requireConfiguration(['authorization_url', 'client_id', 'redirect_uri']);

        // This is intentionally database-backed instead of session/cache-backed:
        // the browser callback does not carry the Sanctum bearer token and can
        // be handled by a different web worker than the connect request.
        $state = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes($this->stateTtlMinutes());

        RdvPermisOAuthState::query()->create([
            'user_id' => $user->getKey(),
            'state_hash' => $this->stateHash($state),
            'expires_at' => $expiresAt,
        ]);

        Log::info('RdvPermis OAuth state stored', [
            'storage' => 'database',
            'user_id' => $user->getKey(),
            'state_length' => strlen($state),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return config('rdvpermis.authorization_url').(str_contains(config('rdvpermis.authorization_url'), '?') ? '&' : '?')
            .http_build_query([
                'response_type' => 'code',
                'client_id' => config('rdvpermis.client_id'),
                'redirect_uri' => config('rdvpermis.redirect_uri'),
                'scope' => implode(' ', config('rdvpermis.scopes')),
                'state' => $state,
            ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code, string $state): User
    {
        try {
            $this->requireConfiguration(['token_url', 'client_id', 'client_secret', 'redirect_uri']);
        } catch (RuntimeException $exception) {
            Log::warning('RdvPermis OAuth token exchange configuration failed', [
                'stage' => 'token_exchange',
                'exception' => $exception::class,
                'exception_message' => $this->safeExceptionMessage($exception),
            ]);

            throw new RdvPermisOAuthException('token_exchange', $exception->getMessage());
        }

        if ($code === '') {
            throw new RdvPermisOAuthException('authorization_callback', 'Le code d’autorisation RdvPermis est manquant.');
        }

        $user = $this->consumeState($state);

        Log::info('RdvPermis OAuth token exchange starting', [
            'stage' => 'token_exchange',
            'user_id' => $user->getKey(),
        ]);

        try {
            $response = Http::asForm()->timeout(config('rdvpermis.timeout'))
                ->post(config('rdvpermis.token_url'), [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => config('rdvpermis.client_id'),
                    'client_secret' => config('rdvpermis.client_secret'),
                    'redirect_uri' => config('rdvpermis.redirect_uri'),
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('RdvPermis OAuth connection failure', [
                'stage' => 'token_exchange',
                'exception' => $exception::class,
                'exception_message' => $this->safeExceptionMessage($exception),
            ]);

            throw new RdvPermisOAuthException('token_exchange', 'Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.');
        }

        Log::info('RdvPermis OAuth token exchange response received', [
            'stage' => 'token_exchange',
            'http_status' => $response->status(),
            'has_access_token' => filled($response->json('access_token')),
        ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            Log::warning('RdvPermis OAuth token exchange rejected', [
                'stage' => 'token_exchange',
                'http_status' => $response->status(),
                'provider_error' => $this->safeProviderError($response),
            ]);

            throw new RdvPermisOAuthException('token_exchange', 'La connexion Livret Numérique n’a pas pu être finalisée.');
        }

        Log::info('RdvPermis OAuth token exchange completed', [
            'stage' => 'token_exchange',
            'http_status' => $response->status(),
            'user_id' => $user->getKey(),
        ]);

        try {
            Log::info('RdvPermis OAuth token persistence starting', [
                'stage' => 'token_persistence',
                'user_id' => $user->getKey(),
            ]);

            $this->tokens->store($user, $response->json());

            Log::info('RdvPermis OAuth token persistence completed', [
                'stage' => 'token_persistence',
                'user_id' => $user->getKey(),
            ]);
        } catch (Throwable $exception) {
            Log::error('RdvPermis OAuth token persistence failed', [
                'stage' => 'token_persistence',
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'exception_message' => $this->safeExceptionMessage($exception),
            ]);

            throw new RdvPermisOAuthException('token_persistence', 'La connexion Livret Numérique n’a pas pu être enregistrée.');
        }

        return $user;
    }

    /**
     * Validate and consume a one-time OAuth state value.
     */
    public function consumeState(string $state): User
    {
        Log::info('RdvPermis OAuth state validation starting', [
            'stage' => 'state_validation',
            'storage' => 'database',
            'state_present' => $state !== '',
            'state_length' => strlen($state),
        ]);

        if ($state === '') {
            $this->logStateFailure($state, false, false, false);

            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        $stateHash = $this->stateHash($state);
        $oauthState = RdvPermisOAuthState::query()
            ->where('state_hash', $stateHash)
            ->first();

        if ($oauthState === null || ! hash_equals($oauthState->state_hash, $stateHash)) {
            $this->logStateFailure($state, false, false, false);

            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        if ($oauthState->expires_at->isPast()) {
            $this->logStateFailure($state, true, true, $oauthState->consumed_at !== null, $oauthState->user_id);

            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        // The conditional update makes the state single-use even when the
        // provider/browser retries the callback concurrently.
        $consumed = RdvPermisOAuthState::query()
            ->whereKey($oauthState->getKey())
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now()]);

        if ($consumed !== 1) {
            $oauthState->refresh();
            $this->logStateFailure(
                $state,
                true,
                $oauthState->expires_at->isPast(),
                $oauthState->consumed_at !== null,
                $oauthState->user_id,
            );

            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        try {
            $user = User::query()
                ->without(['monitor', 'student', 'secretary'])
                ->findOrFail($oauthState->user_id);

            Log::info('RdvPermis OAuth state validation completed', [
                'stage' => 'state_validation',
                'storage' => 'database',
                'user_id' => $user->getKey(),
            ]);

            return $user;
        } catch (Throwable $exception) {
            Log::warning('RdvPermis OAuth state user lookup failed', [
                'stage' => 'state_validation',
                'storage' => 'database',
                'state_present' => true,
                'state_length' => strlen($state),
                'state_record_found' => true,
                'state_expired' => false,
                'state_already_consumed' => false,
                'user_id' => $oauthState->user_id,
                'exception' => $exception::class,
                'exception_message' => $this->safeExceptionMessage($exception),
            ]);

            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }
    }

    private function requireConfiguration(array $keys): void
    {
        $environmentNames = [
            'authorization_url' => 'RDVPERMIS_AUTH_URL',
            'token_url' => 'RDVPERMIS_TOKEN_URL',
            'api_url' => 'RDVPERMIS_API_URL',
            'client_id' => 'RDVPERMIS_CLIENT_ID',
            'client_secret' => 'RDVPERMIS_CLIENT_SECRET',
            'redirect_uri' => 'RDVPERMIS_REDIRECT_URI',
            'frontend_callback_url' => 'RDVPERMIS_FRONTEND_CALLBACK_URL',
        ];
        $missing = [];

        foreach ($keys as $key) {
            if (! filled(config("rdvpermis.$key"))) {
                $missing[] = $environmentNames[$key] ?? $key;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'La configuration RECETTE1 RdvPermis est incomplète : '.implode(', ', $missing).'.',
            );
        }
    }

    private function stateHash(string $state): string
    {
        return hash('sha256', $state);
    }

    private function stateTtlMinutes(): int
    {
        return max(1, (int) config('rdvpermis.state_ttl_minutes', 10));
    }

    private function logStateFailure(
        string $state,
        bool $recordFound,
        bool $expired,
        bool $alreadyConsumed,
        int|string|null $userId = null,
    ): void {
        Log::warning('RdvPermis OAuth state validation failed', [
            'stage' => 'state_validation',
            'storage' => 'database',
            'state_present' => $state !== '',
            'state_length' => strlen($state),
            'state_record_found' => $recordFound,
            'state_expired' => $expired,
            'state_already_consumed' => $alreadyConsumed,
            'user_id' => $userId,
        ]);
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        $message = preg_replace('/[\r\n]+/', ' ', $exception->getMessage()) ?? '';
        $message = preg_replace(
            '/\b(access_token|refresh_token|client_secret|code|state)\s*[:=]\s*[^\s&]+/i',
            '$1=[redacted]',
            $message,
        ) ?? '';

        return mb_substr($message, 0, 300);
    }

    private function safeProviderError(Response $response): ?string
    {
        $error = $response->json('error_description') ?? $response->json('error') ?? $response->json('message');

        if (! is_string($error) || $error === '') {
            return null;
        }

        $sanitized = preg_replace('/[\r\n]+/', ' ', $error) ?? '';
        $sanitized = preg_replace(
            '/\b(access_token|refresh_token|client_secret|code)\s*[:=]\s*[^\s&]+/i',
            '$1=[redacted]',
            $sanitized,
        ) ?? '';

        return mb_substr($sanitized, 0, 300);
    }
}
