<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use Exception;

class RefusedAllTrainingProposalRepo
{
    /**
     * @param Reservation $reservation
     * @return mixed
     */
    public static function run(Reservation $reservation)
    {
        try {
            return TrainingProposal::query()
                ->where('reservation_id', $reservation->id)
                ->update(['status' => ProposalStatusEnum::RESERVER->value]);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return null;
        }

    }
}
