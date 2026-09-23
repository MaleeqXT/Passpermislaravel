<?php

namespace App\Repository\V2\Student\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StoreOrEditCartRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $attributes): Model|Builder
    {
        try {
            CheckEvaluationOffreInCartRepo::run(auth()->user()->student,$attributes);

            $cart = auth()->user()
                ->student
                ->carts()
                ->where('status', CartStatusEnum::PENDING)
                ->latest()
                ->first();


            if (!$cart) {
                $cart = auth()->user()
                    ->student
                    ->carts()
                    ->create();
            }

            return $cart->cartDetails()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }
    }
}
