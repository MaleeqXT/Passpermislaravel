<?php

namespace App\Repository\V2\Monitor\Billing;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\User\Informations\Billing;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FetchAllInvoiceRepo
{
    /**
     * Fetch a paginated list of Billing records with optional filtering.
     *
     * @param array $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated result.
     */
    public static function run(array $attributes = []): LengthAwarePaginator
    {
        return Billing::query()
            ->with(['monitor.details'])
            ->when(auth()->user()?->monitor?->id ?? isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
            ->when(isset($attributes['start']), fn($query) => self::applyStartFilter($query, $attributes['start']))
            ->when(isset($attributes['end']), fn($query) => self::applyEndFilter($query, $attributes['end']))
            ->when(isset($attributes['status']) && $attributes['status'] !== 'all', fn($query) => self::applyStatusFilter($query, $attributes['status']))
            ->when(isset($attributes['period']), fn($query) => self::applyPeriodFilter($query, $attributes['period']))
            ->orderBy('from', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->paginate((int) ($attributes['per_page'] ?? 15));
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
        return $query->where('monitor_id', auth()->user()?->monitor?->id ?? $attributes['monitor_id']);
    }

    /**
     * Apply start date filter to the query.
     *
     * @param Builder $query
     * @param string $start
     * @return Builder
     */
    private static function applyStartFilter(Builder $query, string $start): Builder
    {
        return $query->where('from', $start);
    }

    /**
     * Apply end date filter to the query.
     *
     * @param Builder $query
     * @param string $end
     * @return Builder
     */
    private static function applyEndFilter(Builder $query, string $end): Builder
    {
        return $query->where('to', $end);
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, string $status = SituationStatusEnum::ACTIVE->value): Builder
    {
        return $query->where('status', $status);
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
        return $query->whereBetween('from', [$start_at, $end_at]);
    }
}
