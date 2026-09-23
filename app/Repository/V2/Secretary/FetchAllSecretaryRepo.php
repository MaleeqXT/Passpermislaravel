<?php

namespace App\Repository\V2\Secretary;

use App\Models\Secretary;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchAllSecretaryRepo
{
    /**
     * @param array|null $attributes
     * @param array $with
     * @param array $withCount
     * @return LengthAwarePaginator
     */
    public static function run(array $attributes = [], array|string|null $zoneIds = null, array $with = [], array $withCount = []): LengthAwarePaginator
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return Secretary::query()

            ->with(array_merge(['user'], $with))
            ->when(request()->get('search'), function ($query) use ($searchTerm) {
                $query->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', $searchTerm)
                      ->orWhere('last_name', 'like', $searchTerm)
                      ->orWhere('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm)
                      ->orWhere('phone', 'like', $searchTerm)
                      ->orWhere('adresse', 'like', $searchTerm)
                      ->orWhere('postal', 'like', $searchTerm)
                      ->orWhere('ville', 'like', $searchTerm);
                });
            })
               ->when(request()->get('status'), function ($query) {
    $query->whereHas('user', function ($q) {
        $q->where('status', request()->get('status'));
    });
})
            ->withCount($withCount)
            ->paginate();
    }
}
