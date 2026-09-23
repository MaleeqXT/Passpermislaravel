<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Database\Eloquent\Builder;

class FetchAllSaleRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return Sale::query()
            ->when(isset($attributes['zone_id']), fn($query) => self::applyZoneFilter($query, $attributes['zone_id']))
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->when(request()->get('method'), fn($query) => self::applyMethodFilter($query))
            ->when(request()->get('status'), fn($query) => self::applyStatusFilter($query))
            ->when(
                filled($attributes['start'] ?? null) && filled($attributes['end'] ?? null),
                fn ($query) => self::applyDateRangeFilter($query, $attributes)
            )
            ->when(auth()->user()?->student ?? isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            ->when(isset($attributes['is_tranche']), fn($query) => self::applyTrancheFilter($query))
            ->with('student', 'student.user', 'cart')
            ->with([
                'cart.cartDetails' => function ($query) {
                    $query->withTrashed()->with(['offer' => function ($q) {
                        $q->withTrashed();
                    }]);
                }
            ])
            ->orderBy('created_at', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->paginate();
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
        return $query->where(function ($query) use ($searchTerm) {
            $query->whereHas('student.user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm);
            })
                ->orWhere('reference', 'like', $searchTerm)
                ->orWhere('payment_id', 'like', $searchTerm);
        });
    }

    /**
     * Apply zone filter to the query.
     *
     * @param Builder $query
     * @param string $zoneId
     * @return Builder
     */
    private static function applyZoneFilter(Builder $query, string $zoneId): Builder
    {
        return $query->whereHas('student.user', fn($subQuery) => $subQuery->where('zone_id', $zoneId));
    }

    /**
     * Apply method filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyMethodFilter(Builder $query): Builder
    {
        return $query->where('payment_method', request()->get('method'));
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query): Builder
    {
        return $query->where('payment_status', request()->get('status'));
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        $start_date = $attributes['start'];
        $end_date = $attributes['end'];
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array|null $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes = null): Builder
    {
        return $query->where('student_id', auth()->user()?->student?->id ?? $attributes['student_id']);
    }

    /**
     * Apply tranche filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyTrancheFilter(Builder $query): Builder
    {
        return $query->whereRelation('cartDetails', 'tranches', '>', 1);
    }
}
