<?php

namespace App\Repository\V2\Monitor\Instructor\Car;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use Exception;

class EditCarRepo
{
    /**
     * @param Car $car
     * @param array $attributes
     * @return bool
     */
    public static function run(Car $car, array $attributes = []): bool
    {
        try {
            return $car->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }
       
    }
}
