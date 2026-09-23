<?php

namespace App\Repository\V2\Student\Account\V3\Call;

use App\Models\Roles\Student\User\Information\Call;
use Exception;

class EditCallRepo
{
    /**
     * @param Call $call
     * @param array $attributes
     * @return bool
     */
    public static function run(Call $call, array $attributes): bool
    {
        try {
            return $call->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
