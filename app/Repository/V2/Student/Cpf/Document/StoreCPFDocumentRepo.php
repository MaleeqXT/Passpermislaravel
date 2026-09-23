<?php

namespace App\Repository\V2\Student\Cpf\Document;

use App\Models\Roles\Student\Cpf\Cpf;
use App\Models\Roles\Student\Cpf\CpfVerificationDocument;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class StoreCPFDocumentRepo
{
    /**
     * @param Cpf $cpf
     * @param array|null $attributes
     * @return CpfVerificationDocument|Model
     * @throws Exception
     */
    public static function run(Cpf $cpf, array $attributes = null): CpfVerificationDocument|Model
    {
        try {
            return CpfVerificationDocument::query()->updateOrCreate(
                array_merge(
                    ['cpf_student_id' => $cpf->id],
                    Arr::only($attributes, ['document'])
                ),
                Arr::except($attributes, ['document'])
            );
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
