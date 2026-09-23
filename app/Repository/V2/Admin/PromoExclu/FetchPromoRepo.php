<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Illuminate\Database\Eloquent\Builder;

class FetchPromoRepo
{


    /**
     * Fetch a single promo record with optional filtering.
     *
     * @param array|null $attributes Filtering attributes.
     * @param string|null $user_id User ID for filtering.
     * @return Promo|null The fetched Promo record or null if not found.
     */
    public static function run(array $attributes = null, string $user_id = null): ?Promo
    {
        $promo = Promo::query()
            ->when(isset($attributes['type']), fn($query) => self::applyTypeFilter($query, $attributes['type']))
            ->when(isset($attributes['is_active']), fn($query) => self::applyIsActiveFilter($query, $attributes['is_active']))
            ->when(isset($attributes['start_at'], $attributes['end_at']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(isset($attributes['date']), fn($query) => self::applySpecificDateFilter($query, $attributes['date']))
            ->when($user_id, fn($query) => self::applyUserFilter($query, $user_id))
            ->first();

        // if ($promo && isset($attributes['extra'])) {
        //     $promo->extra_offer = $promo->extraProduct();
        //     $promo->extra_offer_auto = $promo->extraProductAuto();
        // }

        return $promo;
    }

    /**
     * Apply type filter to the query.
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    private static function applyTypeFilter(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Apply is_active filter to the query.
     *
     * @param Builder $query
     * @param bool $isActive
     * @return Builder
     */
    private static function applyIsActiveFilter(Builder $query, bool $isActive): Builder
    {
        return $query->where('is_active', $isActive);
    }

    /**
     * Apply date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('start_at', '>=', $attributes['start_at'])
            ->where('end_at', '<=', $attributes['end_at']);
    }

    /**
     * Apply specific date filter to the query.
     *
     * @param Builder $query
     * @param string $date
     * @return Builder
     */
    private static function applySpecificDateFilter(Builder $query, string $date): Builder
    {
        return $query->where('start_at', '<=', $date)
            ->where('end_at', '>=', $date);
    }

    /**
     * Apply user filter to the query.
     *
     * @param Builder $query
     * @param string $user_id
     * @return Builder
     */
    private static function applyUserFilter(Builder $query, string $user_id): Builder
    {
        return $query->where('user_id', $user_id);
    }
}
