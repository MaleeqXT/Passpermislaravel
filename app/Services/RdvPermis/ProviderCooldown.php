<?php

namespace App\Services\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use Illuminate\Support\Facades\Cache;

class ProviderCooldown
{
    public function key(string $channel, string|int $userId): string
    {
        // Separate authentication and API limits, scoped to this provider/client/user.
        return 'rdvpermis:cooldown:'.hash('sha256', implode('|', [
            $channel, config('rdvpermis.api_url'), config('rdvpermis.token_url'),
            config('rdvpermis.client_id'), $userId,
        ]));
    }

    public function assertAvailable(string $channel, string|int $userId): void
    {
        $until = Cache::store(config('rdvpermis.cache_store'))->get($this->key($channel, $userId));
        $seconds = max(0, (int) $until - now()->timestamp);
        if ($seconds > 0) {
            throw new RdvPermisApiException('RdvPermis reçoit trop de demandes. Réessayez plus tard.', 429, retryAfter: $seconds);
        }
    }

    public function record(string $channel, string|int $userId, ?string $header): int
    {
        $seconds = 60; // Fallback backoff only; this is not a provider quota.
        if ($header !== null && preg_match('/^\d+$/', $header)) {
            $seconds = max(1, min(86400, (int) $header));
        } elseif ($header !== null && ($date = strtotime($header)) !== false) {
            $seconds = max(1, min(86400, $date - now()->timestamp));
        }
        $cache = Cache::store(config('rdvpermis.cache_store'));
        $key = $this->key($channel, $userId);
        $until = max((int) $cache->get($key), now()->timestamp + $seconds);
        $cache->put($key, $until, $until - now()->timestamp);

        return $until - now()->timestamp;
    }
}
