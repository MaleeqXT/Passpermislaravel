<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllReservationInNotHaveReviewRepo
{
    /**
     * @param array|null $attributes
     * @param bool $is_paginate
     * @return Collection|Builder
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null, bool $is_paginate = true): Collection|Builder
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return Reservation::query()
            ->with(['training.student'])
            ->whereHas('training.student')
            ->whereDoesntHave('reviewMonitor')
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->when(isset($attributes['monitor_id']), fn($query) => $query->where('monitor_id', $attributes['monitor_id']))
            ->when(isset($attributes['student_id']), fn($query) => $query->whereRelation('training', 'student_id', $attributes['student_id']))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->where('lieu_id', $attributes['lieu_id']))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['reservation_id']), fn($query) => $query->where('id', $attributes['reservation_id']))
            ->when(isset($attributes['lessDate']), fn($query) => self::applyLessDateFilter($query, $attributes))
            ->orderByDesc('date')
            ->get();
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
        return $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery
                ->whereRelation('monitor.user', 'name', 'like', $searchTerm)
                ->orWhereRelation('monitor.user', 'email', 'like', $searchTerm)
                ->orWhereRelation('monitor.user', 'phone', 'like', $searchTerm)
                ->orWhereRelation('training.student.user', 'name', 'like', $searchTerm)
                ->orWhereRelation('training.student.user', 'email', 'like', $searchTerm)
                ->orWhereRelation('training.student.user', 'phone', 'like', $searchTerm)
                ->orWhereRelation('lieu', 'name', 'like', $searchTerm)
                ->orWhereRelation('lieu.zone', 'name', 'like', $searchTerm);
        });
    }

    /**
     * Apply less date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyLessDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->where(function ($query) use ($attributes) {
            $query->whereDate('date', '<', $attributes['lessDate'])
                ->orWhere(function ($query) use ($attributes) {
                    $query->whereDate('date', '=', $attributes['lessDate'])
                        ->whereTime('end_at', '<=', $attributes['lessEnd_at']);
                });
        });
    }
}
