<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchPrimaryDataOfStudentTrainingRepo
{
    /**
     * @param array $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes)
    {
        $studentId = getStudentId($attributes);
        return Reservation::query()
            ->with('reviewMonitor', 'monitor', 'training.student.user', 'lieu', 'training.offer')
            ->whereHas('reviewMonitor', function ($query) {
                $query->where('is_estimated', true)->whereNotNull('estimation');
            })
            ->when($studentId, function ($query) use ($studentId) {
                $query->whereRelation('training.student', 'id', $studentId);
            })
            ->orderBy('date')
            ->orderBy('start_at')
            ->first();
    }
}
