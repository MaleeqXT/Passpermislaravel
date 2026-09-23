<?php

namespace App\Repository\V2\Monitor\Billing;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class FetchAllHourInvoiceRepo
{
    /**
     * Fetch a paginated list of Reservations for a given Monitor with optional filtering.
     *
     * @param Monitor $monitor The Monitor object.
     * @param Billing|null $billing The Billing object for date range filtering.
     * @param array $attributes Filtering attributes.
     */
    public static function run(Monitor $monitor, ?Billing $billing, array $attributes = [])
    {
        return Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->with([
                'reviewMonitor',
                'training.student.user:id,name,media',
                'training.cancellation',
                'training.student' => function ($query) {
                    $query->realiseHours();
                },
                'training.offer:id,name,type_offre',
                'lieu.zone',
                'training' => function ($query) {
                    $query->withoutGlobalScope(SoftDeletingScope::class);
                }
            ])
            ->select('*', DB::raw('DATE(date) as datef'))
            ->where(function ($query) {
                $query->whereHas('training', function ($query) {
                    $query->withoutGlobalScope(SoftDeletingScope::class);
                });

            })
            ->when($billing, fn($query) => self::applyBillingFilter($query, $billing))
            ->when(isset($attributes['is_facturable']), fn($query) => self::applyIsFacturableFilter($query))
            ->when(isset($attributes['is_not_facturable']), fn($query) => self::applyIsNotFacturableFilter($query))
            ->when(isset($attributes['period']), fn($query) => self::applyPeriodFilter($query, $attributes['period']))
            ->orderBy('date', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->get()
            ->groupBy('datef');
    }

    /**
     * Apply billing date range filter to the query.
     *
     * @param Builder $query
     * @param Billing $billing
     * @return Builder
     */
    private static function applyBillingFilter(Builder $query, Billing $billing): Builder
    {
        return $query->whereBetween('date', [$billing->from, $billing->to]);
    }

    /**
     * Apply "is facturable" filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsFacturableFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereHas('training', function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('cancellation', fn($query) => $query->where('is_justified', false))
                        ->orWhere(fn($query) => $query->whereDoesntHave('cancellation'));
                })
                    ->withoutGlobalScope(SoftDeletingScope::class);
            });

        });
    }

    /**
     * Apply "is not facturable" filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyIsNotFacturableFilter(Builder $query): Builder
    {
        return $query->whereHas('training', function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class)
                ->whereHas('cancellation', fn($query) => $query->where('is_justified', true));
        });

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
}
