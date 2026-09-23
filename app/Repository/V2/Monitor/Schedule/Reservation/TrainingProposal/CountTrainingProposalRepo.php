<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal;

use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Database\Eloquent\Builder;

class CountTrainingProposalRepo
{
    /**
     * @param array $attributes
     * @return int
     */
    public static function run(array $attributes)
    {

        return TrainingProposal::with(['reservation'])
            ->when(isset($attributes['status']),
                fn (Builder $query) => $query->where('status', $attributes['status']))
            ->when(isset($attributes['date']), fn (Builder $query)=>
                $query->whereRelation('reservation', 'date', $attributes['date'])
                , fn (Builder $query)=> $query->whereRelation('reservation', 'date', '>=', now()->format('Y-m-d')))
            ->when($monitor_id = getMonitorId($attributes), fn (Builder $query) =>
                $query->whereRelation('reservation', 'monitor_id', $monitor_id))
            ->when($student_id = getStudentId($attributes),
                fn (Builder $query) => $query->where('student_id', $student_id))
            ->count();
    }
}
