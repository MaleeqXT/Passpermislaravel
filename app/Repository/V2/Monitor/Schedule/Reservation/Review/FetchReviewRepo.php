<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchReviewRepo
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
        return ReviewMonitor::query()
            ->with(['reservation', 'reservation.monitor.user'])
            ->when(
                isset($attributes['status']),
                fn($query) => $query->where('status', $attributes['status'] === 'all' ? null : $attributes['status']),
                fn($query) => $query->where('status', SituationStatusEnum::ACTIVE->value)
            )
            ->when($is_has_not_comment, fn($query) => $query->whereNull('comment'))
            ->when(isset($attributes['reservation_id']), fn($query) => $query->where('reservation_id', $attributes['reservation_id']))
            ->first();
    }
}
