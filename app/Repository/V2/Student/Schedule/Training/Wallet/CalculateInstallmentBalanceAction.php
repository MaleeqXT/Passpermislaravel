<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Models\Roles\Admin\Offer\Offer;

class CalculateInstallmentBalanceAction
{
    /**
     * Calculate the balance for a single installment.
     *
     * @param Offer $offer
     * @param int $tranches Number of installments/tranches
     * @return int Calculated installment balance
     */
    public static function run(Offer $offer, int $tranches): int
    {
        if ($tranches <= 1) {
            // If not installment, return full balance
            return (int) $offer->balance;
        }

        // For installments, divide total balance by number of tranches
        return (int) floor($offer->balance / $tranches);
    }
}
