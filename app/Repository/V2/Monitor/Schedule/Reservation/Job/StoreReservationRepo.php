<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;

class StoreReservationRepo
{
    /**
     * @param array $attributes
     * @return Reservation|Builder|null
     */
    public static function run(array $attributes): Reservation|Builder|null
    {
        // Let the controller roll back and report database errors instead of
        // converting them to a null reservation and causing a misleading 500.
        if (!isset($attributes['monitor_id'])) {
            $attributes['monitor_id'] = auth()->user()?->monitor?->id;
        }

        return Reservation::query()->create($attributes);
    }
}
