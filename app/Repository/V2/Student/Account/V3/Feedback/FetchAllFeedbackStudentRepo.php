<?php

namespace App\Repository\V2\Student\Account\V3\Feedback;

use App\Models\Roles\Student\User\Information\StudentNote;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Database\Eloquent\Builder;

class FetchAllFeedbackStudentRepo
{
    /**
     * @param array|null $attributes
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return StudentNote::query()
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->when(isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            ->when(isset($attributes['user_id']), fn($query) => self::applyUserFilter($query, $attributes))
            ->with(['student', 'user'])
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
        return $query->where('comment', 'like', $searchTerm);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('student_id', $attributes['student_id']);
    }

    /**
     * Apply user filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyUserFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('user_id', $attributes['user_id']);
    }
}
