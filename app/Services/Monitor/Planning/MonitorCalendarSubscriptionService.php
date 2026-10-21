<?php

namespace App\Services\Monitor\Planning;

use App\Models\Roles\Monitor\Planning\MonitorCalendarSubscription;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Support\Facades\Crypt;

class MonitorCalendarSubscriptionService
{
    /**
     * Return the active subscription, issuing a 256-bit bearer token when the
     * Moniteur has not created one yet.
     */
    public function activeFor(Monitor $monitor): MonitorCalendarSubscription
    {
        $subscription = MonitorCalendarSubscription::query()
            ->where('monitor_id', $monitor->id)
            ->first();

        if ($subscription && ! $subscription->revoked_at) {
            return $subscription;
        }

        return $this->replaceToken($monitor, $subscription);
    }

    /** Replace the token so every previously shared URL stops working. */
    public function regenerateFor(Monitor $monitor): MonitorCalendarSubscription
    {
        $subscription = MonitorCalendarSubscription::query()
            ->where('monitor_id', $monitor->id)
            ->first();

        return $this->replaceToken($monitor, $subscription);
    }

    public function revokeFor(Monitor $monitor): void
    {
        MonitorCalendarSubscription::query()
            ->where('monitor_id', $monitor->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function monitorForToken(string $token): ?Monitor
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $subscription = MonitorCalendarSubscription::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->with('monitor.user')
            ->first();

        if (! $subscription || ! hash_equals($subscription->token_hash, hash('sha256', $token))) {
            return null;
        }

        return $subscription->monitor;
    }

    /** @return array{subscription_url: string, created_at: ?string} */
    public function payload(MonitorCalendarSubscription $subscription): array
    {
        return [
            'subscription_url' => route('api.monitor.calendar.subscription.feed', [
                'token' => Crypt::decryptString($subscription->encrypted_token),
            ]),
            'created_at' => $subscription->created_at?->toIso8601String(),
        ];
    }

    private function replaceToken(Monitor $monitor, ?MonitorCalendarSubscription $subscription = null): MonitorCalendarSubscription
    {
        $token = bin2hex(random_bytes(32));

        $subscription ??= new MonitorCalendarSubscription(['monitor_id' => $monitor->id]);
        $subscription->forceFill([
            'monitor_id' => $monitor->id,
            'encrypted_token' => Crypt::encryptString($token),
            'token_hash' => hash('sha256', $token),
            'revoked_at' => null,
        ])->save();

        return $subscription->fresh();
    }
}
