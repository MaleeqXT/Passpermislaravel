<?php

namespace App\Repository\V2\Admin\Zone;

use App\Models\Roles\Student\User\Student;
use Exception;

class AttachZoneToStudentRepo
{
    /**
     * @param Student $student
     * @param array $attributes
     * @return null
     */
    public static function run(Student $student, array $attributes)
    {
        try {
            return $student->zones()->attach($attributes['zone_id']);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Attache : ' . $e->getMessage());
            return null;
        }

    }
}
