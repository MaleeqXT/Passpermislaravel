<?php

namespace App\Repository\V2\Monitor\Zone;

use App\Models\Roles\Monitor\User\Monitor;
use Exception;

class AttachLieuToMonitorRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return bool
     */
    public static function run(Monitor $monitor, array $attributes):?bool
    {
        try {
            return $monitor->lieux()->attach($attributes['lieu_id']);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Attach : ' . $e->getMessage());
            return true;
        }

    }
}
