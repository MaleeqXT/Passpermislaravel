<?php

namespace App\Repository\V2\Admin\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchAdminRepo
{

    /**
     * Fetch a user record by user ID with optional filtering.
     *
     * @param string $userId The ID of the user.
     * @param array|null $attributes Filtering attributes.
     * @return Model The fetched User record.
     */
    public static function run(string $userId, array $attributes = null): Model
    {
        return User::query()
            ->whereId($userId)
            ->isAdmin()
            ->when(isset($attributes['status']),
                fn($query) => self::applyStatusFilter($query, $attributes['status']),
                fn($query) => self::applyDefaultStatusFilter($query))
            ->firstOrFail();
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
        return $query->where('status', SituationStatusEnum::ACTIVE->value);
    }
}
