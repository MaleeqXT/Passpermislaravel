<?php

namespace App\Repository\V2\Student\Schedule\Sale\Admin;


use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use Exception;


class DestroyCartItemRepo
{
    /**
     * @param CartDetail $cartDetail
     * @return bool|null
     * @throws Exception
     */
    public static function run(CartDetail $cartDetail): ?bool
    {
        try {
            return $cartDetail->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
