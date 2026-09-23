<?php

namespace App\Repository\V2\Student\Schedule\Training\Job\Dashbord;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FetchCoursRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        $attributes ??= [];
        $studentId = $attributes['student_id'] ?? auth()->user()?->student?->id;

        $reservation = Reservation::with([
            'monitor.user:id,name,first_name,media,phone',
            'training.offer:id,name,type_offre,balance',
            'lieu.zone',
            'reviewMonitor.monitor.user:id,name,media,phone'
        ])
            ->select('*', DB::raw('DAY(date) as day, DATE(date) as datef, Hour(start_at) as date_hour'))
            ->whereHas('training.student')
            ->when(
                $studentId,
                fn (Builder $query) => $query->whereRelation('training', 'student_id', $studentId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->when(isset($attributes['is_passed']), function ($query) {
                $query->isPassed();
            }, function ($query) {
                $query->isComme();
            });

        if (isset($attributes['is_passed'])) {
            $reservation->orderByDesc('date')->orderByDesc('start_at');
        } else {
            $reservation->orderBy('date')->orderBy('start_at');
        }

        return $reservation->paginate(50);
    }

    /**
     * Apply filter for passed reservations.
     *
     * @param Builder $query
     * @return void
     */
    private static function applyIsPassedFilter($query)
    {
        $query->where(function ($query) {
            $query->whereDate('date', '<', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('start_at', '<', now()->format('H:i'));
                });
        });
    }

    /**
     * Apply filter for upcoming reservations.
     *
     * @param Builder $query
     * @return void
     */
    private static function applyUpcomingFilter($query)
    {
        $query->where(function ($query) {
            $query->whereDate('date', '>', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('start_at', '>', now()->format('H:i'));
                });
        });
    }
}
