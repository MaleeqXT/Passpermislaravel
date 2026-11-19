<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Models\Roles\Monitor\Schedule\Reservation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreReviewRepo
{
    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Reservation $reservation, array $attributes = []): Model|Builder|null
    {
        try {
            // review_monitors.reservation_id is unique and Formation may already
            // have created the row when it recorded an absence. Update it
            // instead of failing silently on the unique index.
            return $reservation->reviewMonitor()->updateOrCreate(['reservation_id' => $reservation->id], $attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
