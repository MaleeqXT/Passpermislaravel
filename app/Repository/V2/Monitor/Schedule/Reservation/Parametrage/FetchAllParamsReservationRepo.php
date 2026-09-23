<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Parametrage;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use Illuminate\Database\Eloquent\Collection;

class FetchAllParamsReservationRepo
{
    /**
     * @param array|null $attributes
     * @return array|Collection|null
     */
    public static function run(array $attributes = null): array|Collection|null
    {
        return ScheduleSetting::query()
            ->when(getMonitorId($attributes), function ($query) use ($attributes){
                return $query->where('monitor_id', getMonitorId($attributes));
            })
            ->where('status', SituationStatusEnum::ACTIVE->value)
            ->get();
    }
}
