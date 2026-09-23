<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Parametrage;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Exception;

class DestroyParamsReservationRepo
{
    /**
     * @param ScheduleSetting $scheduleSetting
     * @return mixed
     */
    public static function run(ScheduleSetting $scheduleSetting)
    {
        try {
            return Reservation::query()->where('monitor_id', $scheduleSetting->monitor_id)
                ->whereBetween('date', [now(), now()->addWeeks($scheduleSetting->number_weeks)->endOfWeek()])
                ->whereDoesntHave('training')
                ->whereDoesntHave('trainingProposals')
                ->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }

    }
}
