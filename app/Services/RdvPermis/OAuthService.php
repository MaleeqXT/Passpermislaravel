<?php

namespace App\Services\RdvPermis;

use App\Exceptions\RdvPermisOAuthException;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthService
{
    public function __construct(private readonly TokenService $tokens) {}

    public function authorizationUrl(User $user): string
    {
        $this->requireConfiguration(['authorization_url', 'client_id', 'redirect_uri']);

        $state = Str::random(64);
        Cache::put($this->stateKey($state), $user->getKey(), now()->addMinutes(10));

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
            throw new RdvPermisOAuthException('token_exchange', $exception->getMessage());
        }

        if ($code === '') {
            throw new RdvPermisOAuthException('authorization_callback', 'Le code d’autorisation RdvPermis est manquant.');
        }

        $user = $this->consumeState($state);

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
            ]);

            throw new RdvPermisOAuthException('token_exchange', 'Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.');
        }

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            Log::warning('RdvPermis OAuth token exchange rejected', [
                'stage' => 'token_exchange',
                'http_status' => $response->status(),
                'provider_error' => $this->safeProviderError($response),
            ]);

            throw new RdvPermisOAuthException('token_exchange', 'La connexion Livret Numérique n’a pas pu être finalisée.');
        }

        try {
            $this->tokens->store($user, $response->json());
        } catch (\Throwable $exception) {
            Log::error('RdvPermis OAuth token persistence failed', [
                'stage' => 'token_persistence',
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
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
        if ($state === '') {
            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        $userId = Cache::pull($this->stateKey($state));
        if (! $userId) {
            throw new RdvPermisOAuthException('state_validation', 'La demande de connexion a expiré. Veuillez recommencer.');
        }

        try {
            return User::query()->findOrFail($userId);
        } catch (\Throwable $exception) {
            Log::warning('RdvPermis OAuth state user lookup failed', [
                'stage' => 'state_validation',
                'exception' => $exception::class,
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

    private function stateKey(string $state): string
    {
        return "rdvpermis:oauth-state:$state";
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
