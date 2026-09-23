<?php

namespace App\Repository\V2\Admin\Zone;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchZoneRepo
{
    /**
     * Fetch a Zone record by Zone ID with optional filtering.
     *
     * @param Zone $zone The Zone object.
     * @param array|null $attributes Filtering attributes.
     * @return Model|Builder The fetched Zone record.
     */
    public static function run(Zone $zone, array $attributes = null): Model|Builder
    {
        return Zone::query()
            ->where('id', $zone->id)
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
    private static function applyStatusFilter(Builder $query, bool $status =true): Builder
    {
        return $query->where('status', $status);
    }
}
