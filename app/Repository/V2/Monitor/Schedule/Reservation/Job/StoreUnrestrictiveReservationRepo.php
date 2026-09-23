<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\TrainingUnrestricted;

class StoreUnrestrictiveReservationRepo
{
    public static function run(array $attributes)
    {
        return TrainingUnrestricted::create($attributes);
    }
}
