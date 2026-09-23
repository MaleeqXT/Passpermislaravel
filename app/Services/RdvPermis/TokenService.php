<?php

namespace App\Services\RdvPermis;

use App\Models\RdvPermisToken;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TokenService
{
    public function forUser(User $user): ?RdvPermisToken
    {
        return RdvPermisToken::query()->where('user_id', $user->getKey())->first();
    }

    public function accessToken(User $user): string
    {
        $token = $this->forUser($user);
        if (! $token) {
            throw new RuntimeException('Livret Numérique non connecté.');
        }

        if ($token->isAccessTokenValid()) {
            return $token->access_token;
        }

        return $this->refresh($token);
    }

    public function store(User $user, array $payload): RdvPermisToken
    {
        $existingToken = $this->forUser($user);

        return RdvPermisToken::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'access_token' => $payload['access_token'],
                'refresh_token' => $payload['refresh_token'] ?? $existingToken?->refresh_token,
                'access_token_expires_at' => isset($payload['expires_in']) ? now()->addSeconds((int) $payload['expires_in'])->subSeconds(30) : null,
                'refresh_expires_at' => isset($payload['refresh_expires_in'])
                    ? now()->addSeconds((int) $payload['refresh_expires_in'])
                    : $existingToken?->refresh_expires_at,
                'scopes' => isset($payload['scope']) ? preg_split('/\s+/', trim($payload['scope'])) : config('rdvpermis.scopes'),
                'status' => 'connected',
                'last_error' => null,
            ]
        );
    }

    public function refresh(RdvPermisToken $token): string
    {
        if (! filled($token->refresh_token)
            || ($token->refresh_expires_at !== null && $token->refresh_expires_at->isPast())
            || ! filled(config('rdvpermis.token_url'))
            || ! filled(config('rdvpermis.client_id'))
            || ! filled(config('rdvpermis.client_secret'))) {
            return $this->markExpired($token);
        }

        try {
            $response = Http::asForm()->timeout(config('rdvpermis.timeout'))
                ->post(config('rdvpermis.token_url'), [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refresh_token,
                    'client_id' => config('rdvpermis.client_id'),
                    'client_secret' => config('rdvpermis.client_secret'),
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('RdvPermis token refresh connection failure', [
                'user_id' => $token->user_id,
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.');
        }

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            Log::warning('RdvPermis token refresh rejected', [
                'user_id' => $token->user_id,
                'status' => $response->status(),
            ]);

            if (in_array($response->status(), [400, 401], true)) {
                return $this->markExpired($token);
            }

            throw new RuntimeException('Le service RdvPermis n’a pas pu renouveler la connexion. Réessayez plus tard.');
        }

        $updated = $this->store($token->user, $response->json());

        return $updated->access_token;
    }

    public function markNeedsReauthentication(User $user): void
    {
        $token = $this->forUser($user);
        if ($token) {
            $token->update([
                'status' => 'reconnect_required',
                'last_error' => 'RdvPermis rejected the current access token.',
            ]);
        }
    }

    private function markExpired(RdvPermisToken $token): never
    {
        $token->update(['status' => 'reconnect_required', 'last_error' => 'OAuth session expired or refresh failed.']);
        throw new RuntimeException('La connexion Livret Numérique a expiré. Veuillez vous reconnecter.');
    }
}
