<?php

namespace App\Repository\V2\Student\Account\V3\Call;


use App\Models\Roles\Student\User\Information\Call;
use Exception;

class RemoveCallRepo
{
    /**
     * @param Call $call
     * @return bool|null
     * @throws Exception
     */
    public static function run(Call $call): ?bool
    {
        try {
            return $call->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
