<?php

namespace App\Services\Student\Training\Reservation\params;

use App\Models\Roles\Student\Schedule\ScheduleSetting;

interface ReservationPramsInterface
{

    /**
     * @param array $attributes
     * @return ScheduleSetting
     */
    public function createOrUpdate(array $attributes): ScheduleSetting;

    public function calcSettingOfResrvation(array $attributes): mixed;
}
