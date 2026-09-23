<?php

namespace App\Repository\V2\Admin\PromoExclu;

use App\Models\Roles\Admin\Promo\Promo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditOrCreatePromoRepo
{
    /**
     * @param array|null $attributes
     * @return Builder|Model|null
     */
    public static function run(array $attributes = []): Builder|Model|null
    {
        try {
            return Promo::query()->updateOrCreate(
                Arr::only($attributes, ['type']),
                Arr::except($attributes, ['type'])
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create Or Update: ' . $e->getMessage());
            throw new Exception('Error occurred while creating or updating promo: ' . $e->getMessage());
        }
    }
}
