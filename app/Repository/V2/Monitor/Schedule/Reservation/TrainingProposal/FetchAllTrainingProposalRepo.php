<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchAllTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes): Builder|Collection
    {
        return TrainingProposal::with(self::getWith($attributes))
            ->join('reservations', 'reservations.id', '=', 'training_proposals.reservation_id')
            ->select(
                'training_proposals.*',
                DB::raw('DATE(reservations.date) as datef'),
            )
            ->when(
                isset($attributes['search']),
                fn(Builder $query) => self::applySearchFilter($query, $attributes['search'] . '%')
            )
            ->when(isset($attributes['date_1']) && isset($attributes['date_2']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(isset($attributes['upcomming']), fn($query) => self::applyDefaultDateFilter($query, $attributes))
            // ->when(isset($attributes['date']), fn($query) => self::applySpecificDateFilter($query, $attributes), fn($query) => self::applyDefaultDateFilter($query, $attributes))
            ->when($monitor_id = getMonitorId($attributes), fn($query) => self::applyMonitorFilter($query, $monitor_id))
            ->when($student_id = getStudentId($attributes), fn($query) => self::applyStudentFilter($query, $student_id))
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyStartEndTimeFilter($query, $attributes))
            ->when(isset($attributes['start_at']) && isset($attributes['end_at']), fn($query) => self::applyTimeRangeFilter($query, $attributes))
            ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes))
            ->when(isset($attributes['is_active']), fn($query) => self::applyActiveStatusFilter($query, $attributes))
            ->orderBy('reservations.date')
            ->get()
            ->groupBy('datef');
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

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @return Builder
     */
    private static function applySearchFilter(Builder $query, string $searchTerm): Builder
    {
        return $query->whereHas('student.user', fn(Builder $query) => $query->where('name', 'like', $searchTerm)
            ->orWhere('last_name', 'like', $searchTerm)
            ->orWhere('first_name', 'like', $searchTerm)
            ->orWhere('email', 'like', $searchTerm)
            ->orWhere('phone', 'like', $searchTerm));
    }
    /**
     * Apply reservation date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']]));
    }

    /**
     * Apply specific date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applySpecificDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->where('date', $attributes['date']));
    }

    /**
     * Apply default date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDefaultDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->where('date', '>=', now()->format('Y-m-d')));
        // return $query->whereHas('reservation', fn(Builder $query) => isset($attributes['all_dates'])
        //     ? $query->where('date', '>=', now()->format('Y-m-d'))
        //     : $query->where('date', now()->format('Y-m-d')));
    }

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @param int|null $monitor_id
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query, ?string $monitor_id): Builder
    {
        return $query->whereRelation('reservation', 'monitor_id', $monitor_id);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param int|null $student_id
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, ?string $student_id): Builder
    {
        return $query->where('student_id', $student_id);
    }

    /**
     * Apply start and end time filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStartEndTimeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->whereBetween('start_at', [$attributes['start'], $attributes['end']]));
    }

    /**
     * Apply time range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->whereTime('start_at', '<=', $attributes['start_at'])
            ->where(fn($query) => $query->whereTime('end_at', '>=', $attributes['end_at'])
                ->orWhere(fn($query) => $query->whereTime('end_at', '>', $attributes['start_at'])
                    ->whereTime('end_at', '<', $attributes['end_at']))));
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('status', $attributes['status']);
    }

    /**
     * Apply active status filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyActiveStatusFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereHas('reservation', fn(Builder $query) => $query->where('is_active', $attributes['is_active']));
    }
}
