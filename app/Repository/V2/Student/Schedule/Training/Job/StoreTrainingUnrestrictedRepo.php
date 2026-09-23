<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\TrainingUnrestricted;

class StoreTrainingUnrestrictedRepo
{
    public static function run(array $attributes): TrainingUnrestricted
    {
        return TrainingUnrestricted::create($attributes);
    }
}
