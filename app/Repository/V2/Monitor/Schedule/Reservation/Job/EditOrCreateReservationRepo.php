<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditOrCreateReservationRepo
{
    /**
     * @param array $reservation
     * @return Builder|Model|null
     */
    public static function run(array $reservation): Builder|Model|null
    {
        try {
            return Reservation::query()
                ->updateOrCreate(
                    Arr::only($reservation, ['monitor_id', 'date', 'start_at', 'end_at']),
                    Arr::only($reservation, ['hour', 'lieu_id', 'color'])
                );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return null;
        }

    }
}
