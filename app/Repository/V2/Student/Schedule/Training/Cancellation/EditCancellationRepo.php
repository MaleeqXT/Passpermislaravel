<?php

namespace App\Repository\V2\Student\Schedule\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Cancellation;
use Exception;

class EditCancellationRepo
{
    /**
     * @param Cancellation $cancellation
     * @param array|null $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(Cancellation $cancellation, array $attributes = null): bool
    {
        try {
            return $cancellation->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
