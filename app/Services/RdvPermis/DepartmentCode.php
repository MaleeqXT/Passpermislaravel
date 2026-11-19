<?php

namespace App\Services\RdvPermis;

/**
 * RdvPermis expects department numbers on three characters (Swagger example
 * "011"; RECETTE rejects "75" with PARAMETRE_INVALIDE and accepts "075").
 * Only purely numeric one- or two-digit codes are left-padded with zeros;
 * any other value (e.g. "2A", "974") is sent unchanged after trimming.
 */
final class DepartmentCode
{
    public static function normalize(string $code): string
    {
        $code = trim($code);

        return preg_match('/^\d{1,2}$/', $code) === 1 ? str_pad($code, 3, '0', STR_PAD_LEFT) : $code;
    }
}
