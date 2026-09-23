<?php

namespace App\Repository\V2\Admin\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllRepo
{
    /**
     * Fetch a paginated list of users with optional filtering.
     *
     * @param array|null $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated list of users.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return User::query()
            ->isAdmin()
            ->when(
                isset($attributes['search']),
                fn(Builder $query) => self::applySearchFilter($query, $attributes['search'] . '%')
            )
            ->when(
                isset($attributes['status']),
                fn(Builder $query) => self::applyStatusFilter($query, $attributes['status']),
                // fn(Builder $query) => self::applyDefaultStatusFilter($query)
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
        return $query->where(function (Builder $subQuery) use ($searchTerm) {
            $subQuery
                ->where('first_name', 'like', $searchTerm)
                ->orWhere('last_name', 'like', $searchTerm)
                ->orWhere('email', 'like', $searchTerm)
                ->orWhere('phone', 'like', $searchTerm)
                ->orWhere('adresse', 'like', $searchTerm)
                ->orWhere('postal', 'like', $searchTerm)
                ->orWhere('ville', 'like', $searchTerm);
        });
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string|null $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, ?string $status): Builder
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
    // private static function applyDefaultStatusFilter(Builder $query): Builder
    // {
    //     return $query->where('status', SituationStatusEnum::ACTIVE->value);
    // }
}
