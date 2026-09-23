<?php

namespace App\Repository\V2\Admin\Cpf;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\Cpf\Cpf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllCpfRepo
{
    /**
     * @param array|null $attributes
     * @param array $with
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null, array $with = []): LengthAwarePaginator
    {
        return Cpf::query()
            ->with(array_merge(['student.user:name,media,email,id'], $with))
            ->when(request('search'), fn($query) => self::applySearchFilter($query, $attributes['search']))
            ->when(isset($attributes['status']), fn($query) => $query->where('status', $attributes['status']))
            ->when(isset($attributes['offer_id']), fn($query) => $query->where('offer_id', $attributes['offer_id']))
            ->when(
                isset($attributes['student_id']) || auth()->user()?->student,
                fn($query) => $query->where('student_id', auth()->user()?->student?->id ?? $attributes['student_id'])
            )
            ->when(
                isset($attributes['start_at'], $attributes['end_at']),
                fn($query) => $query->whereBetween('start_at', [$attributes['start_at'], $attributes['end_at']])
            )
            ->when(
                isset($attributes['start'], $attributes['end']),
                fn($query) => $query->whereBetween('date_verif', [$attributes['start'], $attributes['end']])
            )
            ->when(isset($attributes['date_verif']), fn($query) => $query->where('date_verif', $attributes['date_verif']))
            ->whereHas('student.user', fn($query) => $query->where('status', SituationStatusEnum::ACTIVE->value))
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
        return $query->where(function ($query) use ($searchTerm) {
            $query->whereHas('student.user', fn($query) => $query->where('name', 'like', $searchTerm))
                ->orWhere('comment', 'like', $searchTerm);
        });
    }
}
