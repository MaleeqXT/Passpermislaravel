<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchReservationDashboardRepo
{
    /**
     * @param array $attributes
     * @param array $with
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function run(array $attributes, array $with = [])
    {
        return Reservation::query()
            ->with(array_merge([
                'training.student.user',
                'training.cancellation',
                'training.student' => fn($query) => $query->realiseHours(),
                'training.offer:id,name,type_offre',
                'lieu.zone',
                'reviewMonitor.monitor.user',
                'monitor.user:id,name',
            ]))
            ->select('*', DB::raw('DATE(date) as datef'))
            ->whereHas('training')
            ->when(isset($attributes['monitor_id']), fn($query) => self::applyMonitoFilter($query, $attributes))
            ->when(!empty($attributes['zone_id']), fn($query) => $query->whereHas('lieu', fn ($lieuQuery) => $lieuQuery->where('zone_id', $attributes['zone_id'])))
            ->where(function ($query) {
                $query->whereDate('date', '>=', now())
                    ->orWhereHas('training.cancellation', fn($query) => $query->whereDate('date', '>=', now()));
            })
            ->take(6)
            ->orderBy('datef')
            ->get()
            ->groupBy('datef');
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyMonitoFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('monitor_id', $attributes['monitor_id']);
    }
}
