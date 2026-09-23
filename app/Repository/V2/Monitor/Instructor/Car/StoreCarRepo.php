<?php

namespace App\Repository\V2\Monitor\Instructor\Car;

use App\Models\Roles\Monitor\User\Monitor;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreCarRepo
{
    /**
     * @param Monitor $monitor
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Monitor $monitor, array $attributes = []): Model|Builder|null
    {
        try {
            return $monitor->cars()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
