<?php

namespace App\Repository\V2\Student\Account\V3\History;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FetchAllTrainingRepo
{
    /**
     * @param Student $student
     * @param array|null $attributes
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(Student $student, array $attributes = null)
    {
        return Reservation::query()
            ->select('*', DB::raw('DATE(date) as datef'))
            ->with([
                'training.student.user:id,name,media',
                'monitor.user:id,name,media',
                'training.offer:id,name,type_offre',
                'training.cancellation',
                'lieu.zone',
                'reviewMonitor'
            ])
            ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
            ->whereRelation('training', 'student_id', $student->id)
            ->orderByDesc('date')
            ->orderByDesc('start_at')
            ->paginate()
            ->groupBy('datef');
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
        return $query->where('monitor_id', $attributes['monitor_id']);
    }
}
