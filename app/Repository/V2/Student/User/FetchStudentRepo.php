<?php

namespace App\Repository\V2\Student\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FetchStudentRepo
{
    /**
     * @param array|null $attributes
     * @param string|null $userId
     * @param array $with
     * @return Model
     */

    public static function run(array $attributes = null, string $userId = null, array $with = []): Model
{
    return User::query()
        ->with('student.zones')
        ->with($with)
        ->when(
            !empty($attributes['user_id']),
            function ($query) use ($attributes) {
                $query->where('id', $attributes['user_id']);
            },
            function ($query) use ($userId) {
                $query->where('id', $userId ?? auth()->user()->id);
            }
        )
        ->isStudent()
        // ✅ status filter hata do — single record fetch karte waqt status restrict nahi karna chahiye
        ->firstOrFail();
}

    // public static function run(array $attributes = null, string $userId = null, array $with = []): Model
    // {
    //     return User::query()
    //         ->with('student.zones')
    //         ->with($with)
    //         ->when(
    //             isset($attributes['user_id']),
    //             function ($query) use ($attributes) {
    //                 $query->where('id', $attributes['user_id']);
    //             },
    //             function ($query) use ($userId) {
    //                 $query->where('id', $userId ?? auth()->user()->id);
    //             }
    //         )
    //         ->isStudent()
    //         ->when(
    //             isset($attributes['status']),
    //             function ($query) use ($attributes) {
    //                 $query->where('status', $attributes['status']);
    //             },
    //             function ($query) {
    //                 $query->where('status', SituationStatusEnum::ACTIVE->value);
    //             }
    //         )
    //         ->firstOrFail();
    // }
}
