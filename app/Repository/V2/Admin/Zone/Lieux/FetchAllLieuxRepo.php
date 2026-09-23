<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllLieuxRepo
{


    /**
     * Fetch a paginated list of lieux within a zone with optional filtering.
     *
     * @param Zone $zone The zone to fetch lieux from.
     * @param array|null $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated list of lieux.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(Zone $zone, array $attributes = null): LengthAwarePaginator
    {
        // This endpoint is also used by the React API calendar. On that route
        // a web-session user may be absent even though the request is valid.
        $user = auth()->user();

        return $zone->lieux()->with('monitors.user:id,name,first_name,last_name')
            ->when(
                request()->get('search'),
                fn(Builder $query) => self::applySearchFilter($query, request()->get('search'))
            )
            ->when(
                isset($attributes['status']),
                fn(Builder $query) => self::applyStatusFilter($query, $attributes['status']),
                fn(Builder $query) => self::applyDefaultStatusFilter($query)
            )
            ->when(
                $user?->hasRole('monitor') && !$user->hasRole('admin') && !isset($attributes['is_all']),
                fn(Builder $query) => self::applyMonitorFilter($query)
            )
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
        $searchTerm = '%' . $searchTerm . '%';
        return $query->where('name', 'like', $searchTerm);
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
        return $status === 'all' ? $query : $query->where('status', $status);
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

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query): Builder
    {
        return $query->whereRelation('monitors', 'monitor_id', auth()->user()->monitor->id);
    }
}
