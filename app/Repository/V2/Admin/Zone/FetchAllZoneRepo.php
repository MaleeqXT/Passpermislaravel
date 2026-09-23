<?php

namespace App\Repository\V2\Admin\Zone;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllZoneRepo
{
    /**
     * Fetch a paginated list of Zones with optional filtering based on various conditions.
     *
     * @param array|null $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated result.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return Zone::query()
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query))
            ->when(
                isset($attributes['status']),
                fn($query) => self::applyStatusFilter($query, $attributes['status']),
                fn($query) => self::applyDefaultStatusFilter($query)
            )
            // Apply role-based filters (Admins & Secretaries see all)
            ->when(
                auth()->user()?->hasRole('monitor') && 
                !auth()->user()?->hasAnyRole(['admin', 'secretary']) && 
                !isset($attributes['is_all']),
                fn($query) => self::applyMonitorRoleFilter($query)
            )
            ->when(
                auth()->user()?->hasRole('student') && 
                !auth()->user()?->hasAnyRole(['admin', 'secretary']),
                fn($query) => self::applyStudentRoleFilter($query)
            )
            ->orderBy('created_at', $attributes['sort'] ?? 'desc')
            ->paginate();
    }

    /**
     * Apply search filter to the query.
     */
    private static function applySearchFilter(Builder $query): Builder
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return $query->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', $searchTerm)
                ->orWhereHas('lieux', fn($query) => $query->where('name', 'like', $searchTerm))
                ->orWhereHas('zips', fn($query) => $query->where('code', 'like', $searchTerm));
        });
    }

    /**
     * Apply status filter.
     */
    private static function applyStatusFilter(Builder $query, string $status): Builder
    {
        if ($status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Apply default status filter (active only).
     */
    private static function applyDefaultStatusFilter(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Apply monitor role filter.
     */
    private static function applyMonitorRoleFilter(Builder $query): Builder
    {
        return $query->whereRelation('lieux.monitors', 'monitor_id', auth()->user()->monitor->id);
    }

    /**
     * Apply student role filter.
     */
    private static function applyStudentRoleFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $postal = auth()->user()->student?->zones()->exists();

            $query->where(function ($query) use ($postal) {
                $query->whereHas('zips', fn($query) =>
                    $query->where('code', 'LIKE', '%' . substr(auth()->user()->postal, 0, 2) . '%')
                )->when(
                    $postal === true,
                    fn($query) => $query->orWhereIn('id', auth()->user()->student?->zones->pluck('id'))
                );
            });
        });
    }
}