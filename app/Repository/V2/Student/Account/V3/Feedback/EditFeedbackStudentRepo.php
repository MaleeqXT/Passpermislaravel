<?php

namespace App\Repository\V2\Student\Account\V3\Feedback;

use App\Models\Roles\Student\User\Information\StudentNote;
use Exception;

class EditFeedbackStudentRepo
{
    /**
     * @param StudentNote $studentNote
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(StudentNote $studentNote, array $attributes): bool
    {
        try {
            return $studentNote->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }
    }
}
