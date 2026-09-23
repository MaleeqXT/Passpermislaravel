<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;
use Exception;

class EditTrainingRepo
{
    /**
     * @param Training $training
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(Training $training, array $attributes): bool
    {
        try {
            return $training->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
