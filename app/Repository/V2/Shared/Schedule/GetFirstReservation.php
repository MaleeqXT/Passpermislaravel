<?php

namespace App\Repository\V2\Shared\Schedule;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetFirstReservation
{
    /**
     * @param array $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes)
    {
$sid = $attributes['student_id']
    ?? auth()->user()?->student?->id;
        return Reservation::query()
            ->with('reviewMonitor', 'monitor', 'training.student.user', 'lieu', 'training.offer')
            ->whereHas('reviewMonitor', function ($query) {
                $query->where('is_estimated', true)->whereNotNull('estimation');
            })
            ->when($sid, function ($query) use ($sid) {
                $query->whereRelation('training.student', 'id', $sid);
            })
            ->orderBy('date')
            ->orderBy('start_at')
            ->first();
    }
}
