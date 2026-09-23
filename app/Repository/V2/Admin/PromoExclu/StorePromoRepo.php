<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StorePromoRepo
{
    public static function run(array $attributes = null): Builder|Model|null
    {
        try {
            return Promo::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
