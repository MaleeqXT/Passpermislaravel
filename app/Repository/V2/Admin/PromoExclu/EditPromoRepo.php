<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Exception;
use Illuminate\Support\Arr;

class EditPromoRepo
{
    public static function run(Promo $promo, array $attributes = null): bool
    {
        try {

            return $promo->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update: ' . $e->getMessage());
            return 'false';
        }
    }
}
