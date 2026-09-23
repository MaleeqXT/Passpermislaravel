<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;

class MonitorInReservationRepo
{
    /**
     * @param array $attributes
     * @return Reservation|null
     */
    public static function run(array $attributes): Reservation|null
    {
        return Reservation::query()
            ->when(isset($attributes['date']), fn(Builder $query) => $query->whereDate('date', $attributes['date']))
            ->when($monitor_id = getMonitorId($attributes), fn(Builder $query) => $query->where('monitor_id', $monitor_id))
            ->when(isset($attributes['start']), fn(Builder $query) => $query->whereTime('start_at', $attributes['start']))
            ->when(isset($attributes['start_at']) && isset($attributes['end_at']), fn(Builder $query) => self::applyTimeRangeFilter($query, $attributes))
            ->when(isset($attributes['is_active']), fn(Builder $query) => $query->where('is_active', $attributes['is_active']))
            ->when(isset($attributes['id_not']), fn(Builder $query) => $query->where('id', '!=', $attributes['id_not']))
            ->first();
    }



    /**
     * Apply the filter for time range between start_at and end_at.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereTime('start_at', '<=', $attributes['start_at'])
            ->where(function ($query) use ($attributes) {
                $query->whereTime('end_at', '>=', $attributes['end_at'])
                    ->orWhere(function ($query) use ($attributes) {
                        $query->whereTime('end_at', '>', $attributes['start_at'])
                            ->whereTime('end_at', '<', $attributes['end_at']);
                    });
            });
    }
}
