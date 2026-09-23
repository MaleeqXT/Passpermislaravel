<?php

namespace App\Repository\V2\Admin\Zone\Zip;

use App\Models\Roles\Admin\Area\Zip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchZipRepo
{
    /**
     * Fetch a Zip record by Zip ID with optional filtering.
     *
     * @param Zip $zip The Zip object.
     * @param array|null $attributes Filtering attributes.
     * @return Model|Builder The fetched Zip record.
     */
    public static function run(Zip $zip, array $attributes = null): Model|Builder
    {
        return Zip::query()
            ->where('id', $zip->id)
            ->when(isset($attributes['status']),
                fn($query) => self::applyStatusFilter($query, $attributes['status']),
                fn($query) => self::applyStatusFilter($query))
            ->firstOrFail();
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string|true $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query,bool $status=true): Builder
    {
        return $query->where('status', $status);
    }


}
