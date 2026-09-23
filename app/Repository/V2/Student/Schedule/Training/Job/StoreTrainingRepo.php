<?php

namespace App\Repository\V2\Student\Schedule\Training\Job;

use App\Models\Roles\Student\Schedule\Training;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreTrainingRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $attributes): Model|Builder
    {
        try {
            return Training::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }


    }
}
