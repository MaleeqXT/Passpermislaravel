<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Examen;

use App\Models\Roles\Student\Exam\StudentExam;
use Exception;
use Illuminate\Support\Arr;

class EditExamenStudentRepo
{
    /**
     * @param StudentExam $examenEleve
     * @param array|null $attributes
     * @return bool
     */
    public static function run(StudentExam $examenEleve, array $attributes = null): bool
    {
        try {
            return $examenEleve->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Update : ' . $e->getMessage());
            return false;
        }

    }
}
