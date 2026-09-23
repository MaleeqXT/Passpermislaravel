<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;

class EditReservationRepo
{
    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return mixed
     */
    public static function run(Reservation $reservation, array $attributes)
    {
        try {
            return $reservation->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
