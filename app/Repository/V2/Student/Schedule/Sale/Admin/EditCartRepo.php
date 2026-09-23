<?php

namespace App\Repository\V2\Student\Schedule\Sale\Admin;


use App\Models\Roles\Admin\Offer\Cart\Cart;
use Exception;


class EditCartRepo
{
    /**
     * @param Cart $cart
     * @param array $attributes
     * @return bool|null
     * @throws Exception
     */
    public static function run(Cart $cart, array $attributes): ?bool
    {
        try {
            return $cart->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
