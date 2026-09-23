<?php

namespace App\Repository\V2\Student\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Models\User;
use App\Models\Roles\Student\User\Student;
use Illuminate\Support\Collection;
use RuntimeException;

class CreateOrUpdateManyCartRepo
{
    public static function run(array $items, ?User $user = null, ?Student $student = null): Collection
    {
        $authUser = auth()->user();
        $userQuery = $student ?? $user?->student ?? $authUser?->student;
        if ($authUser && !$authUser->hasRole('student')&&!$userQuery) {
            throw new RuntimeException('L\'utilisateur authentifié n\'est pas un étudiant.');
        }

        CheckEvaluationOffreInCartRepo::run($userQuery, $items);

        $cart = $userQuery->carts()
            ->where('status', CartStatusEnum::PENDING)
            ->latest()
            ->first();

        if (!$cart) {
            $cart = $userQuery->carts()->create();
        }

        $cartDetails = collect();
        foreach ($items as $attributes) {
            $cartDetails->push($cart->cartDetails()->create($attributes));
        }

        return $cartDetails;
    }
}
