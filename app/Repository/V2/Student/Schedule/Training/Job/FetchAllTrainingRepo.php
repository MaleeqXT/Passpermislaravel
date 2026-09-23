<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchAllTrainingRepo
{
    /**
     * @param array $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes)
    {
        return Reservation::with(['monitor.user', 'training.offer', 'lieu.zone'])
            ->whereHas('training')
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->when(auth()->user()?->student?->id ?? isset($attributes['student_id']), function (Builder $query) use ($attributes) {
                self::applyStudentFilter($query, $attributes);
            })
            ->when(isset($attributes['date_1']) && isset($attributes['date_2']), function (Builder $query) use ($attributes) {
                self::applyDateRangeFilter($query, $attributes);
            }, function (Builder $query) use ($attributes) {
                self::applySingleDateFilter($query, $attributes);
            })
            ->when(isset($attributes['monitor_id']), function (Builder $query) use ($attributes) {
                self::applyMonitorFilter($query, $attributes);
            })
            ->when(isset($attributes['start']) && isset($attributes['end']), function (Builder $query) use ($attributes) {
                self::applyTimeRangeFilter($query, $attributes);
            })
            ->when(isset($attributes['reservation_id']), function (Builder $query) use ($attributes) {
                self::applyReservationIdFilter($query, $attributes);
            })
            ->when(isset($attributes['lieu_id']), function (Builder $query) use ($attributes) {
                self::applyLieuIdFilter($query, $attributes);
            })
            ->orderBy('date_hour')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Apply filter for student ID.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyStudentFilter(Builder $query, array $attributes)
    {
        $studentId = auth()->user()?->student?->id ?? $attributes['student_id'];
        $query->whereHas('training', function (Builder $query) use ($studentId) {
            $query->where('student_id', $studentId);
        });
    }

    /**
     * Apply date range filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes)
    {
        $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']]);
    }

    /**
     * Apply single date filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applySingleDateFilter(Builder $query, array $attributes)
    {
        $query->whereDate('date', $attributes['date'] ?? now());
    }

    /**
     * Apply filter for monitor ID.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyMonitorFilter(Builder $query, array $attributes)
    {
        $query->whereIn('monitor_id', $attributes['monitor_id']);
    }

    /**
     * Apply time range filter.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes)
    {
        $query->whereBetween('start_at', [$attributes['start'], $attributes['end']]);
    }

    /**
     * Apply filter for reservation ID.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyReservationIdFilter(Builder $query, array $attributes)
    {
        $query->where('id', $attributes['reservation_id']);
    }

    /**
     * Apply filter for lieu ID.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyLieuIdFilter(Builder $query, array $attributes)
    {
        $query->where('lieu_id', $attributes['lieu_id']);
    }
}
