<?php

namespace App\Repository\V2\Monitor\Instructor\Cart\GrayCart;

use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use App\Models\Roles\Monitor\User\Informations\Instructor\Car\GrayCarCart;
use App\Repository\V2\Shared\Base\Media\EditManyMediaRepo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreOrEditGrayCarCartRepo
{
    /**
     * @param Car $car
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(Car $car, array $attributes = []): Model|Builder|null
    {
        try {
            $cart = GrayCarCart::query()->updateOrCreate(Arr::only($attributes, ['car_id']));

            EditManyMediaRepo::run(['media' => $attributes['media_carte'] ?? []], $cart);
            return $cart;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create or Update : ' . $e->getMessage());
            return null;
        }
    }
}
