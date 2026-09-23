<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Exception;

class EditTrainingProposalRepo

{
    /**
     * @param TrainingProposal $trainingProposal
     * @param array $attributes
     * @return bool
     */
    public static function run(TrainingProposal $trainingProposal, array $attributes): bool
    {
        try {
            return $trainingProposal->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
