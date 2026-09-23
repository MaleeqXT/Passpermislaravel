<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;

class DestroyAllAvailableReservationRepo
{
    /**
     * @param array $attributes
     * @return bool
     */
    public static function run(array $attributes): bool
    {
        try {
            return Reservation::query()
                ->where('monitor_id', $attributes['monitor_id'])
                ->whereDoesntHave(['training', 'trainingProposals'])
                ->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }
    }
}
