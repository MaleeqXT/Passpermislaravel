<?php

namespace App\Repository\V2\Admin\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;
use Exception;

class EditCpfRepo
{
    /**
     * @param Cpf $cpf
     * @param array $attributes
     * @return bool
     */
    public static function run(Cpf $cpf, array $attributes): bool
    {
        try {
            return $cpf->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Edit: ' . $e->getMessage());
            return false;
        }

    }
}
