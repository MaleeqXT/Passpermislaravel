<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchStudentStatistiqueOfTrainingRepo
{
    /**
     * @param array $attributes
     * @return Training|Collection|Builder|null
     */
    public static function run(array $attributes): Training|Collection|null|Builder
    {
        return Training::query()
            ->with($attributes)
            ->when(isset($attributes['date_1']) && isset($attributes['date_2']), function (Builder $query) use ($attributes) {
                self::applyDateRangeFilter($query, $attributes);
            })
            ->when(isset($attributes['start']) && isset($attributes['end']), function (Builder $query) use ($attributes) {
                self::applyTimeRangeFilter($query, $attributes);
            })
            ->whereHas('reservation', function ($query) {
                self::applyReservationDateFilter($query);
            })
            ->when($student_id = getStudentId($attributes), function (Builder $query) use ($student_id) {
                self::applyStudentIdFilter($query, $student_id);
            })
            ->when(isset($attributes['reservation_id']), function (Builder $query) use ($attributes) {
                self::applyReservationIdFilter($query, $attributes);
            })
            ->when(isset($attributes['offer_id']), function (Builder $query) use ($attributes) {
                self::applyOfferIdFilter($query, $attributes);
            })
            ->when(isset($attributes['lieu_id']), function (Builder $query) use ($attributes) {
                self::applyLieuIdFilter($query, $attributes);
            })
            ->orderByDesc('date');
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
     * Apply time range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyTimeRangeFilter(Builder $query, array $attributes)
    {
        $query->whereHas('reservation', function (Builder $query) use ($attributes) {
            $query->whereBetween('start_at', [$attributes['start'], $attributes['end']]);
        });
    }

    /**
     * Apply reservation date filter.
     *
     * @param Builder $query
     * @return void
     */
    private static function applyReservationDateFilter(Builder $query)
    {
        $query->whereDate('date', '<', now())
            ->orWhere(function ($query) {
                $query->whereDate('date', '=', now())
                    ->whereTime('end_at', '<', now()->format('H:S'));
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
     * Apply lieu ID filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyLieuIdFilter(Builder $query, array $attributes)
    {
        $query->whereHas('reservation', function (Builder $query) use ($attributes) {
            $query->where('lieu_id', $attributes['lieu_id']);
        });
    }
}
