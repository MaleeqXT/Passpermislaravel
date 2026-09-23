<?php

namespace App\Repository\V2\Monitor\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchMonitorRepo
{
    /**
     * @param array|null $attributes
     * @param string|null $userId
     * @return Model
     */
    public static function run(array $attributes = null, string $userId = null): Model
    {
        $targetId = $attributes['monitor_id']
            ?? $attributes['user_id']
            ?? $userId
            ?? auth()->user()?->id;

        return User::query()
            ->where(function (Builder $query) use ($targetId) {
                // Accept the users.id used by a direct monitor login and the
                // monitors.id used when an admin opens a monitor dashboard.
                $query->whereKey($targetId)
                    ->orWhereHas('monitor', fn(Builder $monitorQuery) => $monitorQuery->whereKey($targetId));
            })
            // A valid monitor profile is defined by the monitors table.
            // Some existing records do not have the legacy Spatie role row,
            // so filtering only by isMonitor() incorrectly returns 404.
            ->whereHas('monitor')
            ->with('monitor.details', 'monitor.lieux')
            ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes['status']), fn($query) => self::applyStatusFilter($query, SituationStatusEnum::ACTIVE->value))
            ->firstOrFail();
    }

    /**
     * Apply user ID filter to the query.
     *
     * @param Builder $query
     * @param string $userId
     * @return Builder
     */
    private static function applyUserIdFilter(Builder $query, string $userId): Builder
    {
        return $query->where('id', $userId);
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
