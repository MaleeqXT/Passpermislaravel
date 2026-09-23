<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FetchAllTrainingProposalByAdminRepo
{
    /**
     * @param array $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes)
    {
        return TrainingProposal::with(self::getWith($attributes))
            ->join('reservations', 'reservations.id', '=', 'training_proposals.reservation_id')
            ->select(
                'training_proposals.*',
                DB::raw('DATE(reservations.date) as datef'),
            )
            ->when(
                isset($attributes['date_1']) && isset($attributes['date_2']),
                fn(Builder $query) => $query->whereHas('reservation', fn(Builder $query) => $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']])),
                fn(Builder $query) => $query->whereHas('reservation', fn(Builder $query) => $query->where('date', '>=', now()->format('Y-m-d')))
            )
            ->when(
                $monitor_id = getMonitorId($attributes),
                fn(Builder $query) => $query->whereRelation('reservation', 'monitor_id', $monitor_id)
            )
            ->when(
                $student_id = getStudentId($attributes),
                fn(Builder $query) => $query->where('student_id', $student_id)
            )
            ->when(
                isset($attributes['start']) && isset($attributes['end']),
                fn(Builder $query) => $query->whereHas('reservation', fn(Builder $query) => $query->whereBetween('start_at', [$attributes['start'], $attributes['end']]))
            )
            ->when(
                isset($attributes['start_at']) && isset($attributes['end_at']),
                fn(Builder $query) => self::applyReservationFilter($query, $attributes)
            )
            ->when(
                isset($attributes['is_active']),
                fn(Builder $query) => $query->whereHas('reservation', fn(Builder $query) => $query->where('is_active', $attributes['is_active']))
            )
            ->when(isset($attributes['status']), fn(Builder $query) => $query->where('status', $attributes['status']))
            ->orderBy('datef', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->paginate()
            ->groupBy('datef');
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyReservationFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas(
            'reservation',
            fn(Builder $query) => $query->whereTime('start_at', '<=', $attributes['start_at'])
                ->where(fn($query) => $query->whereTime('end_at', '>=', $attributes['end_at'])
                    ->orWhere(fn($query) => $query->whereTime('end_at', '>', $attributes['start_at'])
                        ->whereTime('end_at', '<', $attributes['end_at'])))
        );
    }

    /**
     * Get monitor id.
     *
     * @param array $attributes
     * @return array
     */
    private static function getWith(array $attributes): array
    {
        return [
            'reservation.monitor.user:id,name,media,email,phone',
            'reservation.lieu.zone',
            'student.user:id,name,media,email,phone'
        ];
    }
}
