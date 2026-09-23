<?php

namespace App\Repository\V2\Admin\Zone;

use App\Models\Roles\Student\User\Student;
use Exception;

class DetachZoneToStudentRepo
{
    /**
     * @param Student $student
     * @param array $attributes
     * @return int
     */
    public static function run(Student $student, array $attributes): int
    {

        try {
            return $student->zones()->detach($attributes['zone_id']);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Detaches : ' . $e->getMessage());
            return false;
        }

    }
}
