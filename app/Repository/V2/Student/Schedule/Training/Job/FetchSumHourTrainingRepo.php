<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;

class FetchSumHourTrainingRepo
{
    /**
     * @param array $attributes
     * @return int
     */
    public static function run(array $attributes): int
    {
        return Reservation::query()
            ->when(isset($attributes['date']), function ($query) use ($attributes) {
                $query->where('date', $attributes['date']);
            })
            ->when(isset($attributes['student_id']), function ($query) use ($attributes) {
                $query->whereRelation('training', 'student_id', $attributes['student_id']);
            })
            ->when(isset($attributes['monitor_id']), function ($query) use ($attributes) {
                $query->where('monitor_id', $attributes['monitor_id']);
            })
            ->sum('hour');
    }
}
