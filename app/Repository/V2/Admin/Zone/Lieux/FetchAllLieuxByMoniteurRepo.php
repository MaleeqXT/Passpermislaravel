<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllLieuxByMoniteurRepo
{
    /**
     * Fetch a list of zones with related lieux and monitors.
     *
     * @param array|null $attributes Filtering attributes.
     * @return Collection The list of zones.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): Collection
    {
        return Zone::query()
            ->with('lieux')
            ->whereRelation('lieux.monitors', 'monitor_id', self::getMonitorId($attributes))
            ->when(
                request()->get('search'),
                fn(Builder $query) => self::applySearchFilter($query, request()->get('search'))
            )
            ->get();
    }

    /**
     * Get the monitor ID from the attributes or authenticated user.
     *
     * @param array|null $attributes
     * @return string|null
     */
    private static function getMonitorId(?array $attributes): ?string
    {
        return auth()->user()->monitor->id ?? $attributes['monitor_id'] ?? null;
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
        return $query->where('name', 'like', $searchTerm)
            ->orWhereRelation('lieux', 'name', 'like', $searchTerm);
    }
}
