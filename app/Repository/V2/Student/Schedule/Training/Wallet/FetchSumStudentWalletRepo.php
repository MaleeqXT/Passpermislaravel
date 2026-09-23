<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;

class FetchSumStudentWalletRepo
{
    /**
     * @param Student $student
     * @param array|null $attributes
     * @return int
     */
    public static function run(Student $student, array $attributes = null): int
    {
        return $student->wallets()
            ->when(isset($attributes['offer_id']), function (Builder $query) use ($attributes) {
                self::applyOfferIdFilter($query, $attributes);
            })
            ->when(isset($attributes['status']), function (Builder $query) use ($attributes) {
                self::applyStatusFilter($query, $attributes);
            })
            ->sum('balance');
    }

    /**
     * Apply offer_id filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyOfferIdFilter($query, array $attributes)
    {
        $query->where('offer_id', $attributes['offer_id']);
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applyStatusFilter($query, array $attributes)
    {
        if ($attributes['status'] == 'all') {
            return;
        }
        $query->where('status', $attributes['status']);
    }
}
