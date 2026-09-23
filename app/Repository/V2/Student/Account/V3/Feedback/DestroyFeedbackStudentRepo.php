<?php

namespace App\Repository\V2\Student\Account\V3\Feedback;


use App\Models\Roles\Student\User\Information\StudentNote;
use Exception;


class DestroyFeedbackStudentRepo
{
    /**
     * @param StudentNote $studentNote
     * @return bool|null
     * @throws Exception
     */
    public static function run(StudentNote $studentNote): ?bool
    {
        try {
            return $studentNote->delete();
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }
    }
}
