<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreLieuxRepo
{
    /**
     * @param Zone $zone
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Zone $zone, array $attributes): Model|Builder|null
    {
        // Bubble database errors up to the controller. Returning null here
        // previously produced a false 201 success response on the live API.
        return $zone->lieux()->create($attributes);
    }
}
