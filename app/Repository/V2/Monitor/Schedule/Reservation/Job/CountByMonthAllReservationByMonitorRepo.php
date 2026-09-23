<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CountByMonthAllReservationByMonitorRepo
{
    /**
     * @param array $attributes
     * @return \Illuminate\Support\Collection
     */
    public static function run(array $attributes): \Illuminate\Support\Collection
    {
        return Reservation::query()
            ->select(
                DB::raw('DATE(date) as datef'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM trainings WHERE trainings.reservation_id = reservations.id) THEN 1 ELSE 0 END) as reserved'),
                // DB::raw('SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM trainings WHERE trainings.reservation_id = reservations.id) THEN 1 ELSE 0 END) as dispo')
                DB::raw('SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM trainings WHERE trainings.reservation_id = reservations.id) AND DATE(date) > CURDATE() THEN 1 ELSE 0 END) as dispo')
            )
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
            // ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            // ->when(isset($attributes['disp']), fn($query) => self::applyDispFilter($query, $attributes))
            // ->when(isset($attributes['zone_id']), fn($query) => self::applyZoneFilter($query, $attributes))
            // ->when(isset($attributes['lieu_id']), fn($query) => self::applyLieuFilter($query, $attributes))
            ->groupBy('datef')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->datef => [
                    'reserved' => $row->reserved,
                    'dispo' => $row->dispo
                ]
            ]);
        // ->pluck('count', 'datef');
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereBetween('date', [
            $attributes['start'] ?? now()->subMonth()->lastOfMonth(CarbonInterface::MONDAY),
            $attributes['end'] ?? now()->addMonth()->firstOfMonth(CarbonInterface::SUNDAY),
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
        return $query->whereHas('training', fn($query) => $query->where('student_id', $attributes['student_id']));
    }

    /**
     * Apply disp filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDispFilter(Builder $query, array $attributes): Builder
    {
        return $query->doesntHave('training.offer');
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
}
