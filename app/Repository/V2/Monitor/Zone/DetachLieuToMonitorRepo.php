<?php

namespace App\Repository\V2\Monitor\Zone;


use App\Models\LocationMoniteur;
use App\Models\Roles\Monitor\User\Monitor;
use Exception;

class DetachLieuToMonitorRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return bool|null
     */
    public static function run(Monitor $monitor, array $attributes): ?bool
    {
        try {
            return $monitor->lieux()->detach($attributes['lieu_id']);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Detach : ' . $e->getMessage());
            return null;
        }

    }
}
