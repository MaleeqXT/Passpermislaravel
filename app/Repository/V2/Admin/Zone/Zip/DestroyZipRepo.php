<?php

namespace App\Repository\V2\Admin\Zone\Zip;

use App\Models\Roles\Admin\Area\Zip;
use App\Models\Roles\Admin\Promo\Promo;
use Exception;


class DestroyZipRepo
{
    /**
     * @param Zip $zip
     * @return bool|null
     */
    public static function run(Zip $zip): ?bool
    {
        try {
            return $zip->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }
    }
}
