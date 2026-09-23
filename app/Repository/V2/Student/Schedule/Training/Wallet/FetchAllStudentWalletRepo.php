<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FetchAllStudentWalletRepo
{
    /**
     * @param Student $student
     * @param array|null $attributes
     * @param array $with
     * @return object
     */
    public static function run(Student $student, array $attributes = null, array $with = []): object
    {
        // Get wallets and enrich with purchase breakdown information from sales/carts
        $wallets = $student->wallets()
            ->with([
                'offer',
                'student',
            ])
            ->when(isset($attributes['search']), function (Builder $query) use ($attributes) {
                self::applySearchFilter($query, $attributes);
            })
            ->withSum(['cartDetails' => function (Builder $query) use ($student) {
                $query->getCart($student);
            }], 'quantity')
            ->when(isset($attributes['offer_id']), function (Builder $query) use ($attributes) {
                self::applyOfferIdFilter($query, $attributes);
            })
            ->when(isset($attributes['status']), function (Builder $query) use ($attributes) {
                self::applyStatusFilter($query, $attributes);
            })
            ->with($with)
            ->paginate();

        // Enrich each wallet with detailed purchase information from sales
        $wallets->getCollection()->transform(function($wallet) use ($student) {
            return self::enrichWalletWithPurchaseDetails($wallet, $student);
        });

        // Group wallets by offer_id to ensure multiple wallet rows for the same offer
        // (e.g., installments + later purchases) are combined for frontend display.
        $collection = $wallets->getCollection();
        $grouped = [];

        foreach ($collection as $w) {
            $offerId = (string) ($w->offer_id ?? $w->id ?? '');
            if (!isset($grouped[$offerId])) {
                // Convert to array but preserve the offer relationship
                $grouped[$offerId] = array_merge(
                    $w->toArray(),
                    [
                        'offer' => $w->offer ? $w->offer->toArray() : null
                    ]
                );
            } else {
                // Sum numeric balance fields when duplicates exist
                $grouped[$offerId]['balance'] = (int) ($grouped[$offerId]['balance'] ?? 0) + (int) ($w->balance ?? 0);
                $grouped[$offerId]['total_balance'] = (int) ($grouped[$offerId]['total_balance'] ?? 0) + (int) ($w->total_balance ?? 0);
                $grouped[$offerId]['one_time_balance'] = (int) ($grouped[$offerId]['one_time_balance'] ?? 0) + (int) ($w->one_time_balance ?? 0);
                $grouped[$offerId]['installment_total_balance'] = (int) ($grouped[$offerId]['installment_total_balance'] ?? 0) + (int) ($w->installment_total_balance ?? 0);
                // Keep offer from the first occurrence
            }
        }

        // Replace paginator collection with grouped results
        $wallets->setCollection(collect(array_values($grouped)));

        return $wallets;
    }

    /**
     * Enrich wallet with detailed purchase information from sales/carts.
     * This determines if the wallet contains one-time, installment, or mixed purchases.
     *
     * @param $wallet
     * @param Student $student
     * @return mixed
     */
    private static function enrichWalletWithPurchaseDetails($wallet, Student $student)
    {
        $offerId = $wallet->offer_id;

        // Query sales for this student+offer to get purchase breakdown by installment type
        $salesData = DB::table('sales')
            ->join('carts', 'sales.cart_id', '=', 'carts.id')
            ->join('cart_details', 'carts.id', '=', 'cart_details.cart_id')
            ->where('sales.student_id', $student->id)
            ->where('cart_details.offer_id', $offerId)
            ->where('carts.deleted_at', null)
            ->where('cart_details.deleted_at', null)
            ->select(
                'cart_details.tranches',
                DB::raw('SUM(sales.balance) as total_balance'),
                DB::raw('COUNT(DISTINCT sales.id) as purchase_count')
            )
            ->groupBy('cart_details.tranches')
            ->get();

        // Categorize purchases into one-time and installments
        $oneTimeBalance = 0;
        $installmentData = [];
        $maxTranches = 1;

        foreach ($salesData as $sale) {
            $tranches = (int)($sale->tranches ?? 1);
            $balance = (int)($sale->total_balance ?? 0);

            if ($tranches <= 1) {
                $oneTimeBalance += $balance;
            } else {
                $installmentData[] = [
                    'count' => $tranches,
                    'total' => $balance
                ];
                $maxTranches = max($maxTranches, $tranches);
            }
        }

        // Calculate per-installment balance from the first installment purchase
        $perInstallmentBalance = 0;
        if (!empty($installmentData)) {
            $perInstallmentBalance = (int)floor($installmentData[0]['total'] / $installmentData[0]['count']);
        }

        // Set properties for frontend
        $wallet->one_time_balance = $oneTimeBalance;
        $computedInstallmentTotal = array_reduce(
            $installmentData,
            fn($sum, $item) => $sum + $item['total'],
            0
        );
        $wallet->installment_total_balance = $computedInstallmentTotal;
        $wallet->per_installment_balance = $perInstallmentBalance;

        // Prefer the actual wallet usable balance as the source of truth for frontend display.
        // This ensures reservations that decrement `wallet.balance` are immediately visible.
        if (isset($wallet->balance)) {
            $wallet->total_balance = (int)$wallet->balance;

            // If this wallet is an installment wallet, expose its installment values from the wallet itself
            if (isset($wallet->balance_type) && $wallet->balance_type === 'installment') {
                $wallet->installment_total_balance = (int)$wallet->balance;
                $wallet->per_installment_balance = (int)$wallet->balance;
                $wallet->installments = $maxTranches;
                $wallet->is_installment = $maxTranches > 1;
            } else {
                // Non-installment wallet: keep computed installment/historical values but use wallet balance as total
                $wallet->installments = $maxTranches;
                $wallet->is_installment = $maxTranches > 1;
            }
        } else {
            // Fallback to computed totals from sales if wallet balance is not available
            $wallet->total_balance = $oneTimeBalance + $wallet->installment_total_balance;
            $wallet->multi_payment = $maxTranches;
            $wallet->installments = $maxTranches;
            $wallet->is_installment = $maxTranches > 1;
        }

        return $wallet;
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return void
     */
    private static function applySearchFilter($query, array $attributes)
    {
        $query->whereHas('offer', function ($query) use ($attributes) {
            $query->where('name', 'like', '%' . $attributes['search'] . '%');
        });
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
