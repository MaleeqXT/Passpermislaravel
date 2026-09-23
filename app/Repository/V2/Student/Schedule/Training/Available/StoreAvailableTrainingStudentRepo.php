<?php

namespace App\Repository\V2\Student\Schedule\Training\Available;

use App\Models\Roles\Student\Schedule\StudentAvailability;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreAvailableTrainingStudentRepo
{

    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $attributes): Model|Builder
    {
        try {
            $attributes['user_id'] = auth()->id();
            return StudentAvailability::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
