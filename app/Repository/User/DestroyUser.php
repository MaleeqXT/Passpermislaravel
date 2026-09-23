<?php

namespace App\Repository\User;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class DestroyUser
{
    /**
     * Delete the user and its student record if exists
     *
     * @param User $user
     * @return bool|null
     */
 public static function run(User $user): ?bool
    {
        DB::beginTransaction();
        try {
            // Delete profile photo
            $user->deleteProfilePhoto();

            // Delete all API tokens
            $user->tokens()->delete();

            // Delete related student (if exists)
            $student = $user->student()->withTrashed()->first();
            if ($student) {
                // Delete related carts and trainings
                $student->carts()->each(function ($cart) {
                    $cart->cartDetails()->delete();
                    $cart->delete();
                });
                $student->trainings()->delete();
                $student->ratings()->delete();
                $student->reviewMonitor()->delete();
                $student->forceDelete();
            }

            // Delete user itself
            $user->forceDelete();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
