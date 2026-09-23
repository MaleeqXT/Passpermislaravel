<?php

namespace App\Repository\V2\Shared\Competency;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreCompetencyRepo
{
    /**
     * @param MainCompetency $mainCompetency
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(MainCompetency $mainCompetency, array $attributes): Model|null
    {
        try {
            return $mainCompetency->competencies()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
