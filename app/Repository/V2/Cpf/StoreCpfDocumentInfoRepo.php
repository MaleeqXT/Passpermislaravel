<?php

namespace App\Repository\V2\Cpf;

use App\Models\CPFDocumentInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreCpfDocumentInfoRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder|null
     */
    public static function run(array $attributes = []): Model|Builder|null
    {
        try {
            return CPFDocumentInfo::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create Or Update : ' . $e->getMessage());
            return null;
        }

    }
}
