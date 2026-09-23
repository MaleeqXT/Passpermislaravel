<?php

namespace App\Services\RdvPermis;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
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
        $this->requireConfiguration(['token_url', 'client_id', 'client_secret', 'redirect_uri']);
        if ($code === '') {
            throw new RuntimeException('Le code d’autorisation RdvPermis est manquant.');
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
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.');
        }

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            report(new RuntimeException('Échec de l’échange OAuth RdvPermis : HTTP '.$response->status()));
            throw new RuntimeException('La connexion Livret Numérique n’a pas pu être finalisée.');
        }

        $this->tokens->store($user, $response->json());

        return $user;
    }

    /**
     * Validate and consume a one-time OAuth state value.
     */
    public function consumeState(string $state): User
    {
        if ($state === '') {
            throw new RuntimeException('La demande de connexion a expiré. Veuillez recommencer.');
        }

        $userId = Cache::pull($this->stateKey($state));
        if (! $userId) {
            throw new RuntimeException('La demande de connexion a expiré. Veuillez recommencer.');
        }

        return User::query()->findOrFail($userId);
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
}
