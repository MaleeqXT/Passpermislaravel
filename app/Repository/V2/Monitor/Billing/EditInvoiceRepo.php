<?php

namespace App\Repository\V2\Monitor\Billing;

use App\Models\Roles\Monitor\User\Informations\Billing;
use Exception;

class EditInvoiceRepo
{
    /**
     * @param Billing $billing
     * @param array $attributes
     * @return bool
     */
    public static function run(Billing $billing, array $attributes = []): bool
    {
        try {
            return $billing->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
