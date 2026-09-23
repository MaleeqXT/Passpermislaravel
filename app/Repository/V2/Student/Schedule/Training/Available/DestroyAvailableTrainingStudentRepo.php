<?php

namespace App\Repository\V2\Student\Schedule\Training\Available;


use App\Models\Roles\Student\Schedule\StudentAvailability;
use Exception;


class DestroyAvailableTrainingStudentRepo
{
    /**
     * @param StudentAvailability $studentAvailability
     * @return bool|null
     * @throws Exception
     */
    public static function run(StudentAvailability $studentAvailability): ?bool
    {
        try {
            return $studentAvailability->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
