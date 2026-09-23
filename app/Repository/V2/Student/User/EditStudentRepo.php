<?php

namespace App\Repository\V2\Student\User;

use App\Models\Roles\Student\User\Student;
use Exception;
use Illuminate\Database\Eloquent\Model;

class EditStudentRepo
{
    /**
     * @param Student|string $student
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(Student|string $student, array $attributes): bool
    {
        try {
            if ($student instanceof Model) {
                return $student->update($attributes);
            } else {
                $update = Student::query()->where('user_id', $student)->firstOrFail();
            }
            return $update->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
