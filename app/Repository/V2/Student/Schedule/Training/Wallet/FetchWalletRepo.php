<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\Roles\Student\User\Wallet;
use Illuminate\Database\Eloquent\Builder;

class FetchWalletRepo
{
    /**
     * @param string $studentId
     * @param array|null $attributes
     * @return Wallet|Builder|null
     */
    public static function run(string $studentId, array $attributes = null): Wallet|Builder|null
    {
        return Wallet::query()
            ->where('student_id', $studentId)
            ->when(isset($attributes['offer_id']), fn($query) => $query->where('offer_id', $attributes['offer_id']))
            ->where('status', WalletTypeEnum::ACTIVE->value)
            ->with('offer')
            ->first();
    }
}
