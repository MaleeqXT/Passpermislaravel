<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SumHoursTrainingStudentRepo
{
    /**
     * @param array $attributes
     * @return int|mixed
     */
    public static function run(array $attributes): mixed
    {
        return Reservation::query()
            ->when(isset($attributes['student_id']), fn($query) => $query->whereRelation('training', 'student_id', $attributes['student_id']))
            ->when(!empty($attributes['offer_ids']), fn($query) => $query->whereHas('training', fn($trainingQuery) => $trainingQuery->whereIn('offer_id', $attributes['offer_ids'])))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->where('lieu_id', $attributes['lieu_id']))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['date']), fn($query) => $query->whereDate('date', $attributes['date']))
            ->when(isset($attributes['is_passed']), fn($query) =>$query->isPassed() )
            ->when(isset($attributes['is_coming']), fn($query) => $query->isComme())
            ->sum('hour');
    }
}
