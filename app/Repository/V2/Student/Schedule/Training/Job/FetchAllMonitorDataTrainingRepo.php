<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchAllMonitorDataTrainingRepo
{
    /**
     * @param array $attributes
     * @return Builder[]|Collection
     */
    public static function run(array $attributes): Monitor|Collection
    {

        return Monitor::query()
            ->with('user')
            ->whereRelation('reservations.training.student', 'id', $attributes['student_id'])
            ->get();
    }
}
