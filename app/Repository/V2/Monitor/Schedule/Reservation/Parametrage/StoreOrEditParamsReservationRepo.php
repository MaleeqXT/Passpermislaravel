<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Parametrage;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class StoreOrEditParamsReservationRepo
{
    /**
     * @param array $attributes
     * @return ScheduleSetting|Builder|null
     */
    public static function run(array $attributes): ScheduleSetting|Builder|null
    {
        try {
            $attributes['monitor_id'] = auth()->user()?->monitor?->id;
            return ScheduleSetting::query()->updateOrCreate(
                ['monitor_id' => $attributes['monitor_id']],
                $attributes
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return null;
        }

    }
}
