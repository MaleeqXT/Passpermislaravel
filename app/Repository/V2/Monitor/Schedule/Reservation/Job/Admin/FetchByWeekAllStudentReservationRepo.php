<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByWeekAllStudentReservationRepo
{
    /**
     * @param Student $student
     * @param array $attributes
     * @return Collection|array
     */
    public static function run(Student $student, array $attributes): Collection|array
    {
        return Reservation::query()
            ->with([
                'training.student' => fn($query) => $query->realiseHours(),
                'training.student.user:id,name,media',
                'training.cancellation',
                'monitor.user:id,name,media',
                'training.offer:id,name,type_offre',
                'lieu.zone',
            ])
            ->where(fn($query) => self::applyStudentOrNoTrainingFilter($query, $student))
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->when(
                isset($attributes['start']) && isset($attributes['end']),
                fn($query) => $query->whereBetween('date', [$attributes['start'], $attributes['end']]),
                fn($query) => self::applyDefaultDateRange($query)
            )
            ->orderBy('start_at')
            ->get()
            ->groupBy('datef')
            ->transform(fn($item, $k) => $item->sortBy('day')->groupBy('date_hour'));
    }

    /**
     * Apply filter for student relation or no training.
     *
     * @param Builder $query
     * @param Student $student
     * @return Builder
     */
    private static function applyStudentOrNoTrainingFilter(Builder $query, Student $student): Builder
    {
        return $query->whereRelation('training', 'student_id', $student->id)
            ->orWhereDoesntHave('training');
    }

    /**
     * Apply default date range filter for the current week.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyDefaultDateRange(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(CarbonInterface::MONDAY),
            now()->endOfWeek(CarbonInterface::SUNDAY)
        ]);
    }
}
