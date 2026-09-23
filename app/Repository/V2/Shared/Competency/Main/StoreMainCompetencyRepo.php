<?php

namespace App\Repository\V2\Shared\Competency\Main;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreMainCompetencyRepo
{
    /**
     * @param array $attributes
     * @return Model|null
     */
    public static function run(array $attributes): Model|null
    {
        try {
            return MainCompetency::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
