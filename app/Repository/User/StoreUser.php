<?php

namespace App\Repository\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class StoreUser
{
    /**
     * @param array $attributes
     * @return Model|Builder
     */
public static function run(array $attributes): Model|Builder
{
    // ✅ Set password: if provided, hash it. If not, use default hashed password.
    $attributes['password'] = isset($attributes['password'])
        ? Hash::make($attributes['password'])
        : '$2y$12$954bqa5nEO3b3Hq8pvVjSuTu14bhd7TvPep3STNa/WLqundxTrZPW';

    $attributes['profile_photo_path'] = $attributes['media'] ?? null;
    $attributes['media'] = $attributes['media'] ?? null;

    $user = User::query()->create(Arr::except($attributes, ['role']));
    $user->assignRole($attributes['role']);

    return $user;
}

}
