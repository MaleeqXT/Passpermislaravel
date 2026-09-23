<?php

namespace App\Repository\V2\Student\Account\V3\Feedback;

use App\Models\Roles\Student\User\Information\StudentNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchFeedbackStudentRepo
{
    /**
     * @param StudentNote $studentNote
     * @param array|null $attributes
     * @return Model|Builder
     */
    public static function run(StudentNote $studentNote, array $attributes = null): Model|Builder
    {
        return $studentNote->load(['user', 'student']);
    }
}
