<?php

namespace App\Repository\V2\Student\Schedule\Sale\Admin;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Database\Eloquent\Builder;

class FetchAllCartsRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return Cart::query()
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query))
            ->when(request()->get('status'), fn($query) => self::applyStatusFilter($query))
            ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            ->when(isset($attributes['is_tranche']), fn($query) => self::applyTrancheFilter($query))
            ->when(isset($attributes['zone_id']), function ($query) use ($attributes) {
                $query->whereHas('student.user', function ($subquery) use ($attributes) {
                    $subquery->where('zone_id', $attributes['zone_id']);
                });
            })
            ->with('student', 'student.user', 'cartDetails.offer', 'sale', 'sales')
            ->orderByDesc('created_at')
            ->paginate();
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @return Builder
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function applySearchFilter(Builder $query): Builder
    {
        $searchTerm = '%' . request()->get('search') . '%';
        return $query->where(function (Builder $cartQuery) use ($searchTerm) {
            $cartQuery->whereHas('student.user', function (Builder $userQuery) use ($searchTerm) {
                $userQuery->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm);
            })->orWhereHas('cartDetails.offer', function (Builder $offerQuery) use ($searchTerm) {
                $offerQuery->where('name', 'like', $searchTerm);
            });
        });
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query): Builder
    {
        return $query->where('status', request()->get('status'));
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('student_id', $attributes['student_id']);
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
