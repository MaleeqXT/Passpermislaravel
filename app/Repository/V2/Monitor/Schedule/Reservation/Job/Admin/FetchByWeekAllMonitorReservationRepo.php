<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByWeekAllMonitorReservationRepo
{
    /**
     * @param array $attributes
     * @return Collection|array
     */
    public static function run(array $attributes): Collection|array
    {
        return Reservation::query()
            ->with([
                'training.student' => fn($query) => $query->realiseHours(),
                'training.cancellation',
                'training.student.user:id,name,media',
                'monitor.user:id,name,media',
                'training.offer:id,name,type_offre',
                'lieu.zone'
            ])
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->when(
                isset($attributes['start']) && isset($attributes['end']),
                fn($query) => $query->whereBetween('date', [$attributes['start'], $attributes['end']]),
                fn($query) => self::applyDefaultDateFilter($query)
            )
            ->when(
                isset($attributes['monitor_id']),
                fn($query) => self::applyMonitorFilter($query, $attributes)
            )
            ->when(
                isset($attributes['student_id']),
                fn($query) => self::applyStudentFilter($query, $attributes)
            )
            ->when(
                isset($attributes['lieu_id']),
                fn($query) => self::applyLieuFilter($query, $attributes)
            )
            ->when(
                isset($attributes['zone_id']),
                fn($query) => self::applyZoneFilter($query, $attributes)
            )
            ->orderBy('start_at')
            ->get()
            ->groupBy(['datef', 'date_hour']);
    }

    /**
     * Apply default date filter to the query when no start/end is provided.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyDefaultDateFilter(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(CarbonInterface::MONDAY),
            now()->endOfWeek(CarbonInterface::SUNDAY)
        ]);
    }

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereIn('monitor_id', $attributes['monitor_id']);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training', fn($query) => $query->whereIn('student_id', $attributes['student_id']));
    }

    /**
     * Apply lieu filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyLieuFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('lieu_id', $attributes['lieu_id']);
    }

    /**
     * Apply zone filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyZoneFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']);
    }
}
