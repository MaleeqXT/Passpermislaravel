<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Exception;

class StoreManyTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @return mixed
     */
    public static function run(array $attributes)
    {
        try {
            foreach ($attributes as $item) {
                TrainingProposal::query()->create($item);
            }
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
