<?php

namespace App\Repository\V2\Student\Schedule\Sale\Admin;


use App\Models\Roles\Admin\Offer\Cart\Cart;
use Exception;


class DestroyCartRepo
{
    /**
     * @param Cart $cart
     * @return bool|null
     * @throws Exception
     */
    public static function run(Cart $cart): ?bool
    {
        try {
            return $cart->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
