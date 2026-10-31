<?php

namespace App\Repository\V2\Shared\Competency\Main;

use App\Models\Roles\Student\User\Competency\MainCompetency;
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
        // Let database errors reach the controller so it can roll back and
        // return a real failure instead of a false 201 success response.
        return MainCompetency::query()->create($attributes);
    }
}
