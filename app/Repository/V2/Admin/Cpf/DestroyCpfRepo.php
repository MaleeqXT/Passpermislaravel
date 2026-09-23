<?php

namespace App\Repository\V2\Admin\Cpf;

use App\Models\Roles\Admin\Contact\ContactUs;
use App\Models\Roles\Student\Cpf\Cpf;
use Exception;

class DestroyCpfRepo
{
    /**
     * @param Cpf $cpf
     * @return bool
     */
    public static function run(Cpf $cpf): bool
    {
        try {
            return $cpf->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete Cpf: ' . $e->getMessage());
            return false;
        }

    }
}
