<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Parametrage;

use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchMonitorParamsReservationRepo
{
    /**
     * @return Collection|Builder[]
     */
    public static function run(array $attributes = null)
    {
        // check if user has monitor
        return ScheduleSetting::query()
            ->when(getMonitorId($attributes), function ($query) use ($attributes) {
                return $query->where('monitor_id', getMonitorId($attributes));
            })
            ->first();
    }
}
