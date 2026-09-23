<?php

namespace App\Repository\V2\Admin\Zone\Zip;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllZipsRepo
{
    /**
     * Fetch a paginated list of Zips for a given Zone with optional filtering.
     *
     * @param Zone $zone The Zone object.
     * @param array|null $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated result.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(Zone $zone, ?array $attributes = []): LengthAwarePaginator
    {
        $query = $zone->zips()->getQuery();

        if (!empty($attributes['search'])) {
            $query = self::applySearchFilter($query, $attributes['search']);
        }

        if (!empty($attributes['status'])) {
            $query = self::applyStatusFilter($query, $attributes['status']);
        } else {
            $query = self::applyDefaultStatusFilter($query);
        }

        $perPage = $attributes['per_page'] ?? 15;

        return $query->paginate($perPage);
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
            $query->where('name', 'like', $searchTerm);
        });
    }



    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, string $status): Builder
    {
        if ($status === 'all') {
            return $query;
        }
        return $query->where('status', $status);
    }

    /**
     * Apply default status filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyDefaultStatusFilter(Builder $query): Builder
    {
        return $query->where('status', true);
    }
}
