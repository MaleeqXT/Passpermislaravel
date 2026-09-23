<?php

namespace App\Repository\V2\Student\User;

use App\Models\Roles\Student\User\Student;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreStudentRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    //    public static function run(User $user, array $attributes): Model|Builder
    public static function run(array $attributes): Model|Builder
    {
        try {
            return Student::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
