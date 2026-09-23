<?php

namespace App\Repository\V2\Student\Schedule\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Cancellation;

class FetchCancellationRepo
{
    /**
     * @param Cancellation $cancellation
     * @return Cancellation
     */
    public static function run(Cancellation $cancellation): Cancellation
    {
        return $cancellation->load([
            'media',
            'training.student.user:id,name,media',
            'training.reservation.monitor.user:id,name,media',
            'training.student' => function ($query) {
                $query->realiseHours();
            }
        ]);
    }
}
