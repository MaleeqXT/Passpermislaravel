<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchAllPromosRepo
{
    /**
     * Fetch all promo records with optional filtering.
     *
     * @param array|null $attributes Filtering attributes.
     * @return Collection Collection of Promo records.
     */
    public static function run(array $attributes = null): Collection
    {
        return Promo::query()
            ->where('type', 'promo')
            ->when(isset($attributes['start_at'], $attributes['end_at']), fn($query) => self::applyDateFilter($query, $attributes))
            ->get();
    }

    /**
     * Apply date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('start_at', '>=', $attributes['start_at'])
            ->where('end_at', '<=', $attributes['end_at']);
    }
}
