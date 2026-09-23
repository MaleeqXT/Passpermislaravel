<?php

namespace App\Repository\V2\Admin\Zone;

use App\Models\Roles\Admin\Area\Zone;
use Exception;

class EditZoneRepo
{
    /**
     * @param Zone $zone
     * @param array $attributes
     * @return bool
     */
    public static function run(Zone $zone, array $attributes): bool
    {
        try {
            return $zone->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }
    }
}
