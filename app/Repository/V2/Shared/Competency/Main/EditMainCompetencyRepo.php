<?php

namespace App\Repository\V2\Shared\Competency\Main;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Exception;

class EditMainCompetencyRepo
{
    /**
     * @param MainCompetency $mainCompetency
     * @param array $attributes
     * @return bool
     */
    public static function run(MainCompetency $mainCompetency, array $attributes): bool
    {
        try {
            return $mainCompetency->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
