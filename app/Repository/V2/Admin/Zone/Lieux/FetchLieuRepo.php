<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Lieu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchLieuRepo
{
    /**
     * Fetch a Lieu record by Lieu ID with optional filtering.
     *
     * @param Lieu $lieu The Lieu object.
     * @param array|null $attributes Filtering attributes.
     * @return Model|Builder The fetched Lieu record.
     */
    public static function run(Lieu $lieu, array $attributes = null): Model|Builder
    {
        return Lieu::query()
            ->where('id', $lieu->id)
            ->when(isset($attributes['status']),
                fn($query) => self::applyStatusFilter($query, $attributes['status']),
                fn($query) => self::applyStatusFilter($query))
            ->firstOrFail();
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param bool $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, bool $status=true): Builder
    {
        return $query->where('status', $status);
    }
}
