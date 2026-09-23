<?php

namespace App\Repository\V2\Shared\Competency\Main;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Exception;

class DestroyMainCompetencyRepo
{
    /**
     * @param MainCompetency $mainCompetency
     * @return bool
     */
    public static function run(MainCompetency $mainCompetency): bool
    {
        try {
            return $mainCompetency->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }

    }
}
