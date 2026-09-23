<?php

namespace App\Repository\V2\Student\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GetStudentRepo
{
    /**
     * @param array|null $attributes
     * @param string|null $userId
     * @param array $with
     * @return Model
     */
    public static function run(array $attributes = null, string $userId = null, array $with = []): Model
    {
        return Student::query()
            ->with('user')
            ->with($with)
            ->when(
                isset($attributes['student_id']),
                function ($query) use ($attributes) {
                    $query->where('id', $attributes['student_id']);
                }
            )
            ->when(
                isset($attributes['status']),
                function ($query) use ($attributes) {
                    $query->where('status', $attributes['status']);
                },
                function ($query) {
                    $query->where('status', SituationStatusEnum::ACTIVE->value);
                }
            )
            ->firstOrFail();
    }
}
