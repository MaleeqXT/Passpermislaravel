<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchTrainingInProposalRepo
{
    /**
     * @param array $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes)
    {

        return Reservation::query()
            ->wherehas('training')
            ->where(function ($query) use ($attributes) {
                $query
                    ->when(isset($attributes['reservation']), function ($query) use ($attributes) {
                        $reservation = $attributes['reservation'];
                        $query->orWhere(function ($query) use ($reservation) {
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
                    });
            })
            ->first();
    }
}
