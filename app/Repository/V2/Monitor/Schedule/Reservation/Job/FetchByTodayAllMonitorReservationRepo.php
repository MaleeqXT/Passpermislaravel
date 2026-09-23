<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByTodayAllMonitorReservationRepo
{
    /**
     * @param array $attributes
     * @param array $with
     * @return array|Collection
     */
    public static function run(array $attributes, array $with = [])
    {
        return Reservation::query()
            ->with(array_merge([
                'training.student.user',
                'training.cancellation',
                'training.student' => fn($query) => $query->realiseHours(),
                'training.offer:id,name,type_offre,balance',
                'lieu.zone',
                'reviewMonitor.monitor.user'
            ], $with))
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->when(isset($attributes['allSession']), fn($query) => self::applyAllSessionFilter($query))
            ->when(isset($attributes['disp']), fn($query) => $query->whereDoesntHave('training'))
            ->when(getMonitorId($attributes), function ($query) use ($attributes) {
                return $query->where('monitor_id', getMonitorId($attributes));
            })
            ->when(isset($attributes['student_id']), fn($query) => $query->whereHas('training', fn($query) => $query->whereIn('student_id', $attributes['student_id'])))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->where('lieu_id', $attributes['lieu_id']))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['date']), fn($query) => $query->whereDate('date', $attributes['date']), fn($query) => $query->whereDate('date', now()))
            ->when(isset($attributes['is_passed']), fn($query) => $query->isPassed())
            ->when(isset($attributes['is_now']), fn($query) => $query->isNow())
            ->when(isset($attributes['is_coming']), fn($query) =>$query->isComme())
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Apply the filter for "allSession".
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyAllSessionFilter(Builder $query): Builder
    {
        // Modify the condition here if needed (currently returns all sessions).
        return $query;
    }

    /**
     * Apply the filter for passed sessions.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsPassedFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereDate('date', '<', now())
                ->whereTime('end_at', '<', now()->format('H:i'));
        });
    }

    /**
     * Apply the filter for current sessions.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsNowFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereDate('date', '=', now())
                ->whereTime('end_at', '=', now()->format('H:i'));
        });
    }

    /**
     * Apply the filter for upcoming sessions.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsCommeFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereDate('date', '>', now())
                ->whereTime('end_at', '>', now()->format('H:i'));
        });
    }
}
