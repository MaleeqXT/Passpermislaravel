<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Lieu;
use Exception;

class EditLieuRepo
{
    /**
     * @param Lieu $lieu
     * @param array $attributes
     * @return bool
     */
    public static function run(Lieu $lieu, array $attributes): bool
    {
        try {
            return $lieu->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
