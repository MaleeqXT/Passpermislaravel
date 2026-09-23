<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchReservationTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @param int $status // ProposalStatusEnum::CANCELLED->value
     * @param string|null $type
     * @return Collection|Builder
     */
    public static function run(array $attributes, int $status = ProposalStatusEnum::CANCELLED->value, string $type = null)
    {
        return Reservation::query()
            ->whereDoesntHave('training')
            ->when($status && is_null($type), fn(Builder $query) => self::applyStatusFilter($query, $status))
            ->when(!is_null($type), fn(Builder $query) => self::applyTypeFilter($query, $status, $type))
            ->when(isset($attributes['reservation']), fn(Builder $query) => self::applyReservationFilter($query, $attributes['reservation']))
            ->first();
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param int $status
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, int $status): Builder
    {
        return $query->where(function ($query) use ($status) {
            $query->whereRelation('trainingProposals', 'status', $status)
                ->orWhereDoesntHave('trainingProposals');
        });
    }

    /**
     * Apply type filter to the query.
     *
     * @param Builder $query
     * @param int $status
     * @param string $type
     * @return Builder
     */
    private static function applyTypeFilter(Builder $query, int $status, string $type): Builder
    {
        return $query->where(function ($query) use ($status, $type) {
            $query->whereRelation('trainingProposals', 'status', $type, $status);
        });
    }

    /**
     * Apply reservation filter to the query.
     *
     * @param Builder $query
     * @param object $reservation
     * @return Builder
     */
    private static function applyReservationFilter(Builder $query, object $reservation): Builder
    {
        return $query->orWhere(function ($query) use ($reservation) {
            $query->whereDate('date', $reservation->date)
                ->where('monitor_id', $reservation->monitor_id)
                ->whereTime('start_at', '<=', $reservation->start_at)
                ->where(function ($query) use ($reservation) {
                    $query->whereTime('end_at', '>=', $reservation->end_at)
                        ->orWhere(function ($query) use ($reservation) {
                            $query->whereTime('end_at', '>', $reservation->start_at)
                                ->whereTime('end_at', '<', $reservation->end_at);
                        });
                });
        });
    }
}
