<?php

namespace App\Repository\V2\Monitor\Instructor\Cart\Assurance;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use App\Models\Roles\Monitor\User\Informations\Instructor\Car\CarAssurance;
use App\Repository\V2\Shared\Base\Media\EditManyMediaRepo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditAssuranceRepo
{
    /**
     * @param Car $car
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Car $car, array $attributes = []): Model|Builder|null
    {
        try {
            $assurance = CarAssurance::query()->updateOrCreate(Arr::only($attributes, ['car_id']));

            EditManyMediaRepo::run(['media' => $attributes['media_assurance'] ?? []], $assurance);
            return $assurance;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create or Update : ' . $e->getMessage());
            return null;
        }
    }
}
