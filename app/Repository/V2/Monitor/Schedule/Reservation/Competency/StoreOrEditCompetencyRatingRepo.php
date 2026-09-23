<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Competency;

use App\Models\Roles\Student\User\Competency\Competency;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditCompetencyRatingRepo
{
    /**
     * Update or create a rating for a specific competency.
     *
     * @param Competency $competency
     * @param array|null $attributes
     * @return Model|null
     */
    public static function run(Competency $competency, array $attributes = null): Model|null
    {
        try {
            return $competency->rating()->updateOrCreate(
                Arr::only($attributes, ['student_id']),
                array_merge(Arr::only($attributes, ['comment', 'rating']), ['monitor_id' => auth()->user()->monitor->id])
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
