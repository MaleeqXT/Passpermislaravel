<?php

namespace App\Repository\V2\Admin\Zone\Zip;

use App\Models\Roles\Admin\Area\Zone;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreZipRepo
{
    /**
     * @param Zone $zone
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Zone $zone, array $attributes): Model|Builder|null
    {
        try {
            return $zone->zips()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
