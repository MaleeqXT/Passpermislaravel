<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Database\Eloquent\Builder;

class StoreTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @return TrainingProposal|Builder
     */
    public static function run(array $attributes): TrainingProposal|null
    {
        try {
            return TrainingProposal::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
