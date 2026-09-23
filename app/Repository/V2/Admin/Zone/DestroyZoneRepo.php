<?php

namespace App\Repository\V2\Admin\Zone;


use App\Models\Roles\Admin\Area\Zone;
use Exception;


class DestroyZoneRepo
{
    /**
     * @param Zone $zone
     * @return bool|null
     */
    public static function run(Zone $zone): ?bool
    {

        try {
            return $zone->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }

    }
}
