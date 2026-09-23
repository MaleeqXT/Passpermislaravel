<?php

namespace App\Repository\V2\Monitor\User;

use App\Models\Roles\Monitor\User\Monitor;
use Exception;

class EditMonitorRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return bool
     */
    public static function  run(Monitor $monitor, array $attributes): bool
    {
        try {
          
            return $monitor->details()->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
