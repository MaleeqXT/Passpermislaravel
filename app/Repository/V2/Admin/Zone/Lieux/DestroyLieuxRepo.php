<?php

namespace App\Repository\V2\Admin\Zone\Lieux;

use App\Models\Roles\Admin\Area\Lieu;
use Exception;


class DestroyLieuxRepo
{
    /**
     * @param Lieu $lieu
     * @return bool|null
     */
    public static function run(Lieu $lieu): ?bool
    {
        try {
            return $lieu->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return false;
        }

    }
}
