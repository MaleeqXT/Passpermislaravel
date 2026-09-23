<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Exception;

class DestroyPromoRepo
{
    /**
     * @param Promo $promo
     * @return bool
     */
    public static function run(Promo $promo): bool
    {
        try {
            return $promo->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete: ' . $e->getMessage());
            return false;
        }
    }
}
