<?php

namespace App\Repository\V2\Monitor\Instructor\License;

use App\Models\Roles\Monitor\User\Informations\Instructor\DriverLicense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchAllLicenseRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null)
    {
        return DriverLicense::query()
            ->where('monitor_id', getMonitorId($attributes))
            ->first();
    }
}
