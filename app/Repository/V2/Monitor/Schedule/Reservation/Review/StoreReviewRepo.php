<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreReviewRepo
{
    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Reservation $reservation, array $attributes = []): Model|Builder|null
    {
        try {
            return $reservation->reviewMonitor()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
