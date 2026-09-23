<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Review;

use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use Exception;

class EditReviewRepo
{
    /**
     * @param ReviewMonitor $reviewMonitor
     * @param array $attributes
     * @return bool
     */
    public static function run(ReviewMonitor $reviewMonitor, array $attributes): bool
    {
        try {
            return $reviewMonitor->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }
    }
}
