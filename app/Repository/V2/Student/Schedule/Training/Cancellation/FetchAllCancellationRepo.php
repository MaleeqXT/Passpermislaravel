<?php

namespace App\Repository\V2\Student\Schedule\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Cancellation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FetchAllCancellationRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return Cancellation::query()
            ->with([
                'training' => fn($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
                'training.student.user:id,name,media,phone,email',
                'training.reservation.monitor.user:id,name,media,phone,email',
                'training.reservation.lieu.zone',
            ])
            // ->whereHas('training.reservation')
            ->when(isset($attributes['zone_id']), fn($query) => self::applyZoneFilter($query, $attributes['zone_id']))
            ->when(isset($attributes['date_1']) && isset($attributes['date_2']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when($monitor_id = getMonitorId($attributes), fn($query) => self::applyMonitorFilter($query, $monitor_id))
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyStartEndDateFilter($query, $attributes))
            ->when($student_id = getStudentId($attributes), fn($query) => self::applyStudentFilter($query, $student_id))
            ->when(isset($attributes['is_justified']), fn($query) => self::applyJustifiedFilter($query, $attributes))
            ->whereHas('training', fn($query) => $query->withoutGlobalScope(SoftDeletingScope::class))
            ->orderByDesc('created_at')
            ->paginate();
    }

    /**
     * Apply date range filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training.reservation', fn($query) => $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']]));
    }

    /**
     * Apply zone filter.
     *
     * @param Builder $query
     * @param string $zoneId
     * @return Builder
     */
    private static function applyZoneFilter(Builder $query, string $zoneId): Builder
    {
        return $query->whereHas('training.student.user', fn($query) => $query->where('zone_id', $zoneId));
    }

    /**
     * Apply monitor filter.
     *
     * @param Builder $query
     * @param string $monitor_id
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query, string $monitor_id): Builder
    {
        return $query->whereHas('training', fn($query) => $query->whereRelation('reservation', 'monitor_id', $monitor_id)->withoutGlobalScope(SoftDeletingScope::class));
    }

    /**
     * Apply start and end date filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStartEndDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('training.reservation', fn($query) => $query->whereBetween('start_at', [$attributes['start'], $attributes['end']]));
    }

    /**
     * Apply student filter.
     *
     * @param Builder $query
     * @param string $student_id
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, string $student_id): Builder
    {
        return $query->whereHas('training', fn($query) => $query->where('student_id', $student_id)->withoutGlobalScope(SoftDeletingScope::class));
    }

    /**
     * Apply justified filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyJustifiedFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('is_justified', (bool)$attributes['is_justified']);
    }
}
