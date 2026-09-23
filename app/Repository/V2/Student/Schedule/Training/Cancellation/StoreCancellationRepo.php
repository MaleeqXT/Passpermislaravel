<?php

namespace App\Repository\V2\Student\Schedule\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Cancellation;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreCancellationRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $attributes): Model|Builder
    {
        try {
            return Cancellation::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;


        }
    }
}
