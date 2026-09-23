<?php

namespace App\Repository\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EditUserPasswordAction
{
    /**
     * @param User $user
     * @param array $attributes
     * @return bool
     */
    public static function run(User $user, array $attributes): bool
    {
        return $user->update([
            'password' => Hash::make($attributes['password'])
        ]);
    }
}
