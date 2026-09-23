<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class DestroyMonitorReservationRepo
{
    /**
     * @param array $attributes
     * @return bool
     */
    public static function run(array $attributes): bool
    {
        try {
            return Reservation::query()
                ->whereDoesntHave('training')
                ->when($monitor_id = getMonitorId($attributes)
                    , function (Builder $query) use ($attributes, $monitor_id) {
                        $query->where('monitor_id', $monitor_id);
                    })
                ->when(isset($attributes['date']),
                    fn($query) => $query->whereDate('date', $attributes['date']))
                ->when(isset($attributes['start_at']) && isset($attributes['end_at']),
                    fn($query) => self::applyTimeRangeFilter($query, $attributes))
                ->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }

    }

    /**
     * Apply time range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->where(function ($query) use ($attributes) {
            $query->where(function ($query) use ($attributes) {
                $query->whereTime('start_at', '>=', $attributes['start_at'])
                    ->whereTime('start_at', '<', $attributes['end_at']);
            })
                ->orWhere(function ($query) use ($attributes) {
                    $query->whereTime('end_at', '>', $attributes['start_at'])
                        ->whereTime('end_at', '<=', $attributes['end_at']);
                })
                ->orWhere(function ($query) use ($attributes) {
                    $query->whereTime('start_at', '<=', $attributes['start_at'])
                        ->whereTime('end_at', '>=', $attributes['end_at']);
                })
                ->orWhere(function ($query) use ($attributes) {
                    $query->whereTime('start_at', '>=', $attributes['start_at'])
                        ->whereTime('end_at', '<=', $attributes['end_at']);
                });
        });
    }
}
