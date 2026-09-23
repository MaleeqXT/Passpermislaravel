<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchAllReservationInTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @param int $status // ProposalStatusEnum::CANCELLED->value
     * @return Collection|Builder
     */
    public static function run(array $attributes, int $status = 3)
    {
        return Reservation::query()
            ->whereDoesntHave('training')
            ->where(fn ($query) =>
                $query->whereRelation('trainingProposals', 'status', $status)
                    ->orWhereDoesntHave('trainingProposals'))
            ->when(isset($attributes['reservation_ids']),
                fn ($query) => $query->whereIn($attributes['reservation_ids']))
            ->first();
    }
}
