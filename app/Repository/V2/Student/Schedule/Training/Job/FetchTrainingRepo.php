<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;

class FetchTrainingRepo
{
    /**
     * @param Training $training
     * @param array $attributes
     * @return Training
     */
    public static function run(Training $training, array $attributes): Training
    {
        return $training->load($attributes);
    }
}
