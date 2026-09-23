<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Models\Roles\Admin\Offer\Order\Sale;

class EditSaleRepo
{
    /**
     * @param Sale $sale
     * @param array $attributes
     * @return Sale
     */
    public static function run(Sale $sale, array $attributes): Sale|null
    {
        try {
            return tap($sale)->update($attributes)->refresh();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to update : ' . $e->getMessage());
            return null;
        }

    }
}
