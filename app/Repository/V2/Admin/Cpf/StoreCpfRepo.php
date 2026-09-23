<?php

namespace App\Repository\V2\Admin\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreCpfRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder| Cpf
     */
    public static function run(array $attributes): Model|null|Cpf
    {
        try {
            return Cpf::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Delete : ' . $e->getMessage());
            return null;
        }
    }
}
