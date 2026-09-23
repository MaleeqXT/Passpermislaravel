<?php

namespace App\Repository\V2\Monitor\Billing;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SumTotalHourInvoiceRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return int
     */
    public static function run(Monitor $monitor, array $attributes = []): int
    {
        return Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training')
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyDateRangeFilter($query, $attributes['start'], $attributes['end']))
            ->when(isset($attributes['period']), fn($query) => self::applyPeriodFilter($query, $attributes['period']))
            ->when(isset($attributes['is_facturable']), fn($query) => self::applyFacturableFilter($query))
            ->when(isset($attributes['is_not_facturable']), fn($query) => self::applyNotFacturableFilter($query))
            ->sum('hour');
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @param string $start
     * @param string $end
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Apply period filter to the query.
     *
     * @param Builder $query
     * @param string $period
     * @return Builder
     */
    private static function applyPeriodFilter(Builder $query, string $period): Builder
    {
        $start_at = Carbon::parse($period)->startOfMonth()->format('Y-m-d');
        $end_at = Carbon::parse($period)->endOfMonth()->format('Y-m-d');
        return $query->whereBetween('date', [$start_at, $end_at]);
    }

    /**
     * Apply facturable filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function  applyFacturableFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query
                //->whereHas('reviewMonitor', fn($query) => $query->whereNotNull('comment'))
                ->where(function ($query) {
                    $query->whereHas('training.cancellation', fn($query) => $query->where('is_justified', false))
                        ->orWhere(fn($query) => $query->whereDoesntHave('training.cancellation'));
                });
        });
    }

    /**
     * Apply not facturable filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyNotFacturableFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query
                //->whereHas('reviewMonitor', fn($query) => $query->whereNull('comment'))
                ->where(function ($query) {
                    $query->whereHas('training.cancellation', fn($query) => $query->where('is_justified', true))
                        ->orWhere(fn($query) => $query->whereDoesntHave('training.cancellation'));
                });
        });
    }
}
