<?php

namespace App\Repository\V2\Shared\Competency;

use App\Models\Roles\Student\User\Competency\Competency;

class DestroyCompetencyRepo
{
    /**
     * @param Competency $competency
     * @return bool
     */
    public static function run(Competency $competency): bool
    {

        return $competency->delete();
    }
}
