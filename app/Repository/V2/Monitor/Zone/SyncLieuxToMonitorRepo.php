<?php

namespace App\Repository\V2\Monitor\Zone;

use App\Models\Roles\Monitor\User\Monitor;
use Exception;

class SyncLieuxToMonitorRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return array
     */
    public static function run(Monitor $monitor, array $attributes)
    {
        try {
            return $monitor->lieux()->sync($attributes['lieux']);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Sync : ' . $e->getMessage());
            return null;
        }

    }
}
