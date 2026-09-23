<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Models\Roles\Student\User\Wallet;
use Exception;

class EditWalletRepo
{
    /**
     * @param Wallet $wallet
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(Wallet $wallet, array $attributes): bool
    {
        try {
            return $wallet->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
