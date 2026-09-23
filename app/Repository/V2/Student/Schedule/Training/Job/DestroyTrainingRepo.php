<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;
use Exception;

class DestroyTrainingRepo
{
    /**
     * @param Training|string $training
     * @return bool
     * @throws Exception
     */
    public static function run(string|Training $training): bool
    {
        try {
            if (is_string($training))
                return Training::query()->findOrFail($training)->delete();

            return $training->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
