<?php

namespace App\Repository\V2\Admin\Zone\Zip;

use App\Models\Roles\Admin\Area\Zip;
use Exception;

class EditZipRepo
{
    /**
     * @param Zip $zip
     * @param array $attributes
     * @return bool
     */
    public static function run(Zip $zip, array $attributes): bool
    {
        try {
            return $zip->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
