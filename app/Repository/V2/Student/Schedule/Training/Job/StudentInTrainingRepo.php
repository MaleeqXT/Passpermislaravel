<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Builder;

class StudentInTrainingRepo
{
    /**
     * @param array $attributes
     * @return Training|null
     */
    public static function run(array $attributes): Training|null
    {
        return Training::query()
            ->when(isset($attributes['date_1']) && isset($attributes['date_2']), function (Builder $query) use ($attributes) {
                self::applyDateRangeFilter($query, $attributes);
            })
            ->when(isset($attributes['date']), function (Builder $query) use ($attributes) {
                self::applyDateFilter($query, $attributes);
            })
            ->when(isset($attributes['start']), function (Builder $query) use ($attributes) {
                self::applyStartFilter($query, $attributes);
            })
            ->when(isset($attributes['start_at']) && isset($attributes['end_at']), function (Builder $query) use ($attributes) {
                self::applyStartEndTimeFilter($query, $attributes);
            })
            ->when($student_id = getStudentId($attributes) && empty($attributes['monitor_id']), function (Builder $query) use ($student_id) {
                self::applyStudentIdFilter($query, $student_id);
            })
            ->when($student_id = getStudentId($attributes) && isset($attributes['monitor_id']), function (Builder $query) use ($student_id, $attributes) {
                self::applyStudentAndMonitorFilter($query, $student_id, $attributes);
            })
            ->when(isset($attributes['reservation_id']), function (Builder $query) use ($attributes) {
                self::applyReservationIdFilter($query, $attributes);
            })
            ->when(isset($attributes['offer_id']), function (Builder $query) use ($attributes) {
                self::applyOfferIdFilter($query, $attributes);
            })
            ->when(isset($attributes['id_not']), function (Builder $query) use ($attributes) {
                self::applyIdNotFilter($query, $attributes);
            })
            ->first();
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes)
    {
        $query->whereHas('reservation', function (Builder $query) use ($attributes) {
            $query->whereBetween('date', [$attributes['date_1'], $attributes['date_2']]);
        });
    }

    /**
     * Apply date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyDateFilter(Builder $query, array $attributes)
    {
        $query->whereRelation('reservation', 'date', $attributes['date']);
    }

    /**
     * Apply start filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyStartFilter(Builder $query, array $attributes)
    {
        $query->whereRelation('reservation', 'start_at', $attributes['start']);
    }

    /**
     * Apply start and end time filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyStartEndTimeFilter(Builder $query, array $attributes)
    {
        $query->whereHas('reservation', function (Builder $query) use ($attributes) {
            $query->whereTime('start_at', '<=', $attributes['start_at'])
                ->where(function ($query) use ($attributes) {
                    $query->whereTime('end_at', '>=', $attributes['end_at'])
                        ->orWhere(function ($query) use ($attributes) {
                            $query->whereTime('end_at', '>', $attributes['start_at'])
                                ->whereTime('end_at', '<', $attributes['end_at']);
                        });
                });
        });
    }

    /**
     * Apply student ID filter to the query.
     *
     * @param Builder $query
     * @param string $student_id
     * @return void
     */
    private static function applyStudentIdFilter(Builder $query, $student_id)
    {
        $query->where('student_id', $student_id);
    }

    /**
     * Apply student and monitor filter to the query.
     *
     * @param Builder $query
     * @param string $student_id
     * @param array $attributes
     * @return void
     */
    private static function applyStudentAndMonitorFilter(Builder $query, $student_id, array $attributes)
    {
        $query->where(function ($query) use ($student_id, $attributes) {
            $query->where('student_id', $student_id)
                ->orWhereRelation('reservation', 'monitor_id', $attributes['monitor_id']);
        });
    }

    /**
     * Apply reservation ID filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyReservationIdFilter(Builder $query, array $attributes)
    {
        $query->where('reservation_id', $attributes['reservation_id']);
    }

    /**
     * Apply offer ID filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyOfferIdFilter(Builder $query, array $attributes)
    {
        $query->where('offer_id', $attributes['offer_id']);
    }

    /**
     * Apply not equal to ID filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyIdNotFilter(Builder $query, array $attributes)
    {
        $query->where('id', '!=', $attributes['id_not']);
    }
}
