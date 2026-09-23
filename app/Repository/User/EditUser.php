<?php

namespace App\Repository\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
class EditUser
{
    /**
     * @param User|string $user
     * @param array $attributes
     * @return bool
     */
    // public static function run(User|string $user, array $attributes): bool
    // {
    //     $user = $user instanceof Model ? $user : User::query()->where('id', $user)->firstOrFail();
    //     // if (isset($attributes['photo']) || isset($attributes['media'])) {
    //     //     $photo = $attributes['photo'] ?? $attributes['media'] ?? null;
    //     //     $attributes['profile_photo_path'] = $photo;
    //     //     $attributes['media'] = $photo;
    //     // }
        
    //     if (isset($attributes['password']) && isset($attributes['password_confirmation'])) {
    //         $attributes['password'] = Hash::make($attributes['password']);
    //         $user->update(Arr::only($attributes, ['password']));
    //     }
    //     return $user->update(Arr::except($attributes, ['password_confirmation', 'password']));
    // }
    public static function run(User|string $user, array $attributes): bool
{
    $user = $user instanceof Model
        ? $user
        : User::query()->where('id', $user)->firstOrFail();

    // Upload image
    if (
        isset($attributes['media']) &&
        $attributes['media'] instanceof UploadedFile
    ) {
        // Optional: purani image delete karo
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Nayi image save karo
        $path = $attributes['media']->store('profile-photos', 'public');

        // Database mein path save hoga
        $attributes['profile_photo_path'] = $path;

        // media key remove kar do
        unset($attributes['media']);
    }

    // Password update
    if (
        !empty($attributes['password']) &&
        !empty($attributes['password_confirmation'])
    ) {
        $attributes['password'] = Hash::make($attributes['password']);
        $user->update(Arr::only($attributes, ['password']));
    }

    return $user->update(
        Arr::except($attributes, ['password', 'password_confirmation'])
    );
}
}
