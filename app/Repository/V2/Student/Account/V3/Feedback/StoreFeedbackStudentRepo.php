<?php

namespace App\Repository\V2\Student\Account\V3\Feedback;

use App\Models\Roles\Student\User\Information\StudentNote;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreFeedbackStudentRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $attributes): Model|Builder
    {
        try {
            $attributes['user_id'] = auth()->id();
            return StudentNote::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }
    }
}
