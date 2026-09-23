<?php

namespace App\Repository\V2\Student\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllStudentRepo
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(
        array $attributes = [],
        array|string $zoneIds = [],
        array $with = [],
        array $withCount = []
    ): LengthAwarePaginator {
        $searchTerms = preg_split('/\s+/', trim((string) request()->get('search', '')), -1, PREG_SPLIT_NO_EMPTY);
        $perPage = is_numeric(request()->get('per_page')) ? (int) request()->get('per_page') : 15;
        $zoneIds = array_values(array_unique(array_filter((array) $zoneIds)));

        return User::withoutGlobalScope(\App\Scopes\Global\User\StatusInactiveUserScoop::class)
            ->when($searchTerms !== [], fn (Builder $query) => self::applySearchFilter($query, $searchTerms))
            ->isStudent()
            ->when($zoneIds !== [], fn ($query) => $query->whereIn('zone_id', $zoneIds))
            ->with([
                'student' => function ($query) {
                    $query->realiseHours();
                },
            ])
            ->with($with)
            ->withCount($withCount)
            ->when(!empty($attributes['status']), function ($query) use ($attributes) {
                if ($attributes['status'] === 'new') {
                    $query->whereDoesntHave('student.wallets');
                    return;
                }

                $query->where('status', $attributes['status']);
            })
            ->paginate($perPage);
    }

    /** Match every word, so "Marie Martin" finds a first-name/last-name pair. */
    private static function applySearchFilter(Builder $query, array $searchTerms): Builder
    {
        foreach ($searchTerms as $term) {
            $like = '%' . $term . '%';
            $query->where(function (Builder $subQuery) use ($like) {
                $subQuery->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('adresse', 'like', $like)
                    ->orWhere('postal', 'like', $like)
                    ->orWhere('ville', 'like', $like);
            });
        }

        return $query;
    }
}
