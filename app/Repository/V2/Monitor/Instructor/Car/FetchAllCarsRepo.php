<?php

namespace App\Repository\V2\Monitor\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllCarsRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator|Collection|Builder
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator|Collection|Builder
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return Car::query()
            ->with('grayCarCart', 'assurance')
            ->where('monitor_id', auth()->user()->monitor->id ?? $attributes['monitor_id'])
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->orderByDesc('created_at')
            ->get();
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
            $query->where('immatriculation', 'like', $searchTerm)
                ->orWhere('marque', 'like', $searchTerm)
                ->orWhere('modele', 'like', $searchTerm)
                ->orWhere('color', 'like', $searchTerm);
        });
    }
}
