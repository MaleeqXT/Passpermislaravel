<?php

namespace App\Repository\V2\Shared\Competency;

use App\Models\Roles\Student\User\Competency\Competency;

class EditCompetencyRepo
{
    /**
     * @param Competency $competency
     * @param array $attributes
     * @return bool
     */
    public static function run(Competency $competency, array $attributes): bool
    {
        try {
            return $competency->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
