<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllReviewMonitorRepo
{
    /**
     * @param array|null $attributes
     * @param bool $is_has_not_comment
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null, bool $is_has_not_comment = false): LengthAwarePaginator
    {
        $searchTerm = '%' . request()->get('search') . '%';
        return ReviewMonitor::query()
            ->with('reservation', 'reservation.monitor.user')
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->when(
                isset($attributes['status']),
                fn($query) => $query->where('status', $attributes['status'] === 'all' ? null : $attributes['status']),
                fn($query) => $query->where('status', SituationStatusEnum::ACTIVE->value)
            )
            ->when($is_has_not_comment, fn($query) => $query->whereNull('comment'))
            ->when(isset($attributes['student_id']), fn($query) => $query->whereRelation('reservation.training', 'student_id', $attributes['student_id']))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->whereRelation('reservation', 'lieu_id', $attributes['lieu_id']))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('reservation.lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['reservation_id']), fn($query) => $query->where('reservation_id', $attributes['reservation_id']))
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
        return $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery
                ->whereRelation('reservation.monitor.user', 'name', 'like', $searchTerm)
                ->orWhereRelation('reservation.monitor.user', 'email', 'like', $searchTerm)
                ->orWhereRelation('reservation.monitor.user', 'phone', 'like', $searchTerm);
        });
    }
}
