<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchReservationRepo
{
    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(Reservation $reservation, array $attributes): Reservation|Builder
    {
        try {
            return $reservation->load($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Load : ' . $e->getMessage());
            return $reservation;
        }

    }
}
