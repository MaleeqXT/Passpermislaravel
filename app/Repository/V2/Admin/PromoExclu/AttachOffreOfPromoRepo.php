<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use App\Models\Roles\Student\Cpf\Cpf;
use Exception;

class AttachOffreOfPromoRepo
{
    /**
     * @param Promo $promo
     * @param array|null $attributes
     * @return array|null
     */
    public static function run(Promo $promo, array $attributes = null): array|null
    {
        try {
            return $promo->offers()->sync($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to sync : ' . $e->getMessage());
            return null;
        }
    }
}
