<?php

namespace App\Repository\V2\Cpf;
use App\Models\CPFDocumentInfo;
use Exception;


class EditCPFDocumentInfoRepo
{
    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @param array $attributes
     * @return bool
     */
    public static function run(CPFDocumentInfo $CPFDocumentInfo, array $attributes = []): bool
    {
        try {
            return $CPFDocumentInfo->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
