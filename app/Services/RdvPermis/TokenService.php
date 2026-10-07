<?php

namespace App\Services\RdvPermis;

use App\Models\RdvPermisToken;
use App\Models\User;
use App\Exceptions\RdvPermisApiException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            throw new RdvPermisApiException('Livret Numérique non connecté.', 401);
        }

        if ($token->isAccessTokenValid()) {
            return $token->access_token;
        }

        return $this->refresh($token);
    }

    public function store(User $user, array $payload): RdvPermisToken
    {
        try {
            $existingToken = $this->forUser($user);

            return RdvPermisToken::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                $this->tokenAttributes($payload, $existingToken),
            );
        } catch (DecryptException $exception) {
            Log::warning('RdvPermis stale encrypted credentials detected', [
                'stage' => 'token_persistence',
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
            ]);

            // Do not hydrate or decrypt the stale row. This affects only the
            // selected user's RDVPermis credentials, then writes fresh values
            // through the encrypted model casts using the current APP_KEY.
            DB::table((new RdvPermisToken)->getTable())
                ->where('user_id', $user->getKey())
                ->delete();

            return RdvPermisToken::query()->create([
                'user_id' => $user->getKey(),
                ...$this->tokenAttributes($payload),
            ]);
        }
    }

    public function refresh(RdvPermisToken $token): string
    {
        // TTL outlasts the bounded HTTP request; a short wait avoids blocking workers.
        $lock = Cache::store(config('rdvpermis.cache_store'))->lock(
            'rdvpermis:refresh:'.hash('sha256', config('rdvpermis.token_url').'|'.config('rdvpermis.client_id').'|'.$token->user_id),
            max(1, (int) config('rdvpermis.timeout', 20)) + 10,
        );
        try {
            return $lock->block((int) config('rdvpermis.refresh_lock_wait_seconds', 2), function () use ($token) {
                $user = new User;
                $user->id = $token->user_id;
                // Never refresh the caller's stale copy of a rotating credential.
                $current = $this->forUser($user);
                if (! $current) {
                    throw new RdvPermisApiException('Livret Numérique non connecté.', 401);
                }
                if ($current->isAccessTokenValid()) {
                    return $current->access_token;
                }
                if (in_array($current->status, ['reconnect_required', 'needs_reauthentication'], true)) {
                    return $this->markExpired($current);
                }
                if ($current->access_token_expires_at === null) {
                    $current->update(['status' => 'connection_unverified', 'last_error' => 'Access-token expiry is unknown.']);
                    throw new RdvPermisApiException('La durée de validité de la connexion est inconnue. Veuillez vous reconnecter.', 401);
                }

                return $this->refreshLocked($current, $user);
            });
        } catch (LockTimeoutException $exception) {
            throw new RdvPermisApiException('Le renouvellement RdvPermis est en cours. Réessayez dans quelques secondes.', 503, retryAfter: 2);
        }
    }

    private function refreshLocked(RdvPermisToken $token, User $user): string
    {
        if (! filled($token->refresh_token)
            || ($token->refresh_expires_at !== null && $token->refresh_expires_at->isPast())
        ) {
            return $this->markExpired($token);
        }
        if (! filled(config('rdvpermis.token_url')) || ! filled(config('rdvpermis.client_id')) || ! filled(config('rdvpermis.client_secret'))) {
            throw new RdvPermisApiException('La configuration RdvPermis est incomplète.', 503);
        }
        $cooldown = app(ProviderCooldown::class);
        $cooldown->assertAvailable('token', $token->user_id);

        try {
            $response = Http::asForm()->timeout(config('rdvpermis.timeout'))->withOptions(['allow_redirects' => false])
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

            throw new RdvPermisApiException('Le service RdvPermis est temporairement inaccessible. Réessayez plus tard.', 503);
        }

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            Log::warning('RdvPermis token refresh rejected', [
                'user_id' => $token->user_id,
                'status' => $response->status(),
            ]);

            if ($response->status() === 429) {
                $retry = $cooldown->record('token', $token->user_id, $response->header('Retry-After'));
                throw new RdvPermisApiException('RdvPermis reçoit trop de demandes. Réessayez plus tard.', 429, retryAfter: $retry);
            }
            if (in_array($response->status(), [400, 401], true)
                && in_array($response->json('error'), ['invalid_grant', 'invalid_token'], true)) {
                return $this->markExpired($token);
            }

            throw new RdvPermisApiException('Le service RdvPermis n’a pas pu renouveler la connexion. Réessayez plus tard.', 503);
        }

        $updated = $this->store($user, $response->json());
        if (! $updated->isAccessTokenValid()) {
            throw new RdvPermisApiException('La durée de validité de la connexion est inconnue. Veuillez vous reconnecter.', 401);
        }

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
        throw new RdvPermisApiException('La connexion Livret Numérique a expiré. Veuillez vous reconnecter.', 401);
    }

    public function readiness(?RdvPermisToken $token): string
    {
        if (! $token) {
            return 'not_connected';
        }
        try {
            if (in_array($token->status, ['reconnect_required', 'needs_reauthentication'], true) || ! filled($token->access_token)) {
                return 'reconnect_required';
            }
            if ($token->access_token_expires_at === null) {
                return 'connection_unverified';
            }
            if ($token->isAccessTokenValid()) {
                return 'connected';
            }
            if (filled($token->refresh_token) && ($token->refresh_expires_at === null || $token->refresh_expires_at->isFuture())) {
                return 'connection_unverified';
            }
        } catch (DecryptException $exception) {
            return 'reconnect_required';
        }

        return 'reconnect_required';
    }

    private function tokenAttributes(array $payload, ?RdvPermisToken $existingToken = null): array
    {
        $hasRefreshToken = array_key_exists('refresh_token', $payload);
        $hasRefreshExpiry = array_key_exists('refresh_expires_in', $payload);

        return [
            'access_token' => $payload['access_token'],
            'refresh_token' => $hasRefreshToken ? $payload['refresh_token'] : $existingToken?->refresh_token,
            'access_token_expires_at' => isset($payload['expires_in']) && (int) $payload['expires_in'] > 0
                ? now()->addSeconds((int) $payload['expires_in'])->subSeconds(30) : null,
            'refresh_expires_at' => $hasRefreshExpiry
                ? now()->addSeconds((int) $payload['refresh_expires_in'])
                : $existingToken?->refresh_expires_at,
            'scopes' => isset($payload['scope']) ? preg_split('/\s+/', trim($payload['scope'])) : config('rdvpermis.scopes'),
            'status' => isset($payload['expires_in']) && (int) $payload['expires_in'] > 0 ? 'connected' : 'connection_unverified',
            'last_error' => null,
        ];
    }
}
