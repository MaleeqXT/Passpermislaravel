<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;

class DestroyReviewRepo
{
    /**
     * @param Reservation $reservation
     * @return bool
     */
    public static function run(Reservation $reservation): bool
    {
        try {
            return $reservation->reviewMonitor()->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }
    }
}
