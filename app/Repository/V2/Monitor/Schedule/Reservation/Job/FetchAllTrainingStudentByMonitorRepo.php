<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FetchAllTrainingStudentByMonitorRepo
{
    /**
     * @param array $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes)
    {
        return Reservation::query()->with([
            'training.student.user',
            'training.cancellation',
            'training.student' => function ($query) {
                $query->realiseHours();
            },
            'training.offer:id,name,type_offre',
            'lieu.zone',
            'reviewMonitor.monitor.user'
        ])
            ->when(getMonitorId($attributes), function ($query) use ($attributes) {
                return $query->where(
                    'monitor_id',
                    getMonitorId($attributes)
                );
            })
            ->when(isset($attributes['student_id']), fn($query) => $query->whereRelation('training', 'student_id', $attributes['student_id']))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->where('lieu_id', $attributes['lieu_id']))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['date']), fn($query) => $query->whereDate('date', $attributes['date']))
            ->when(isset($attributes['is_passed']), fn($query) => $query->isPassed())
            ->when(isset($attributes['is_coming']), fn($query) => $query->isComme())
            ->orderBy('date')
            ->orderBy('start_at')
            ->paginate($attributes['paginate'] ?? 30);
    }


    /**
     * Apply the filter for passed reservations.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsPassedFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereDate('date', '<', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('end_at', '<', now()->format('H:i'));
                });
        });
    }

    /**
     * Apply the filter for upcoming reservations.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsCommeFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereDate('date', '>=', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('start_at', '>=', now()->format('H:i'));
                });
        });
    }
}
