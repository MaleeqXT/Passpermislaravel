<?php

namespace App\Repository\V2\Admin\Contact;

use App\Models\Roles\Admin\Contact\ContactUs;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FetchAllContactRepo
{
    /**
     * @param array $attributes
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes): LengthAwarePaginator
    {
        return ContactUs::query()
            ->when(
                !empty($attributes['search']),
                fn($query) => self::applySearchFilter($query, $attributes['search'])
            )
            ->when(
                isset($attributes['is_read']) && $attributes['is_read'] !== 'all',
                fn($query) => $query->where('is_read', (bool)$attributes['is_read']),
                fn($query) => $query->where('is_read', false)
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
    private static function applySearchFilter($query, string $searchTerm)
    {
        return $query->where(function ($query) use ($searchTerm) {
            $query->where('nom', 'like', "%{$searchTerm}%")
                ->orWhere('prenom', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('phone', 'like', "%{$searchTerm}%")
                ->orWhere('subject', 'like', "%{$searchTerm}%")
                ->orWhere('message', 'like', "%{$searchTerm}%");
        });
    }
}
