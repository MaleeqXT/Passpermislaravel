<?php

namespace App\Repository\V2\Student\Account\V3\Call;

use App\Models\Roles\Student\User\Information\Call;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrganiseCallRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(array $attributes): Model|null
    {
        try {
            $attributes['user_id'] = auth()->id();
            return Call::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return null;
        }

    }
}
