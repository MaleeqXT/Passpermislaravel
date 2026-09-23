<?php

namespace App\Repository\V2\Student\Account\V3\Call;

use App\Models\Roles\Student\User\Information\Call;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class FetchAllCallRepo
{
    /**
     * @param array|null $attributes
     * @return Collection
     */
    public static function run(array $attributes = null)
    {
        return Call::query()
            ->select('*', DB::raw('Hour(time_at) as parsed_hour'))
            ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            ->when(isset($attributes['user_id']), fn($query) => self::applyUserFilter($query, $attributes))
            ->when(isset($attributes['date_start']) && isset($attributes['date_end']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(isset($attributes['date']), fn($query) => self::applyExactDateFilter($query, $attributes))
            ->when(isset($attributes['time']), fn($query) => self::applyTimeFilter($query, $attributes))
            ->with(['student', 'user'])
            ->get()
            ->groupBy(['date']); // Group by date only, removed unnecessary grouping by time_at
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
        return $query->where('student_id', $attributes['student_id']);
    }

    /**
     * Apply user filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyUserFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('user_id', $attributes['user_id']);
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
        return $query->whereBetween('date', [$attributes['date_start'], $attributes['date_end']]);
    }

    /**
     * Apply exact date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyExactDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('date', $attributes['date']);
    }

    /**
     * Apply time filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyTimeFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('time_at', $attributes['time']);
    }
}
