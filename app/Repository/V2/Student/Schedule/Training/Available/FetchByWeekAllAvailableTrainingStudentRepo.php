<?php

namespace App\Repository\V2\Student\Schedule\Training\Available;

use App\Enums\V2\Monitor\Reservation\Training\TrainingStatusEnum;
use App\Models\Roles\Student\Schedule\StudentAvailability;
use App\Models\Roles\Student\User\Student;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchByWeekAllAvailableTrainingStudentRepo
{
    /**
     * @param Student $student
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(Student $student, array $attributes = null): array|Collection
    {
        return StudentAvailability::query()
            ->where('student_id', $student->id)
            ->select('*', DB::raw('DAY(date) as day ,date(date) as datef,  Hour(time_at) as date_hour'))
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(!isset($attributes['start']) || !isset($attributes['end']), fn($query) => self::applyDefaultDateRangeFilter($query))
            ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes))
            ->orderBy('time_at')
            ->get()
            ->groupBy('datef');
    }

    /**
     * Apply the date range filter when start and end dates are provided.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereBetween('date', [$attributes['start'], $attributes['end']]);
    }

    /**
     * Apply the default date range filter if no start and end dates are provided.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyDefaultDateRangeFilter(Builder $query): Builder
    {
        $session_date_start = now()->startOfWeek(CarbonInterface::MONDAY);
        $session_date_end = now()->endOfWeek(CarbonInterface::SUNDAY);
        return $query->whereBetween('date', [$session_date_start, $session_date_end]);
    }

    /**
     * Apply the status filter if provided.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('status', $attributes['status']);
    }
}
