<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Exception;
use Illuminate\Support\Arr;

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
                // A monitor can submit the drawer twice (or double-click) but
                // the same availability must lead to a single proposal for a
                // given student.
                TrainingProposal::query()->firstOrCreate(
                    Arr::only($item, ['reservation_id', 'student_id']),
                    Arr::only($item, ['comment', 'status']),
                );
            }
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
