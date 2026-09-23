<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletBalanceTypeEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use App\Repository\V2\Monitor\Schedule\Reservation\Examen\StoreExamenRepo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreOrEditWalletAction
{
    /**
     * @param Student $student
     * @param array $attributes
     * @return Model|Builder
     */
    public static function run(Student $student, array $attributes): Model|Builder
    {
        // validate that we have an offer id
        if (empty($attributes['offer_id']) || !is_string($attributes['offer_id'])) {
            throw new \InvalidArgumentException('offer_id is required and must be a non-empty string');
        }

        // If force_create is set OR this is an installment payment, always create a new wallet row
        $forceCreate = $attributes['force_create'] ?? false;
        $isInstallment = isset($attributes['installment_no']) && !empty($attributes['installment_no']);

        if ($forceCreate || $isInstallment) {
            // Always create a new wallet row for installments
            $wallet = self::createNewWallet($student, $attributes);
            self::storeExamen($student, $attributes['offer_id']);
        } else {
            // For non-installment payments, try to find and update existing wallet
            $wallet = self::getStudentWallet($student, $attributes['offer_id']);
            if ($wallet) {
                self::updateWalletBalance($wallet, $attributes);
            } else {
                $wallet = self::createNewWallet($student, $attributes);
                self::storeExamen($student, $attributes['offer_id']);
            }
        }

        return $wallet;
    }

    /**
     * Get the student's wallet for the given offer ID.
     *
     * @param Student $student
     * @param string $offer_id
     * @return Wallet|null
     */
    private static function getStudentWallet(Student $student, string $offer_id)
    {
        // caller is responsible for ensuring offer_id is non-null
        return $student->wallets()->where('offer_id', $offer_id)->first();
    }

    /**
     * Update the wallet's balance, balance_type, and status.
     *
     * @param Wallet $wallet
     * @param array $attributes
     * @return void
     */
    private static function updateWalletBalance(Wallet $wallet, array $attributes): void
    {
        $updateData = [
            'balance' => $wallet->balance + $attributes['balance'],
            'status' => $attributes['status'] ?? WalletTypeEnum::ACTIVE->value,
        ];

        // Update balance_type if provided and column exists
        if (isset($attributes['balance_type']) && Schema::hasColumn('wallets', 'balance_type')) {
            $updateData['balance_type'] = $attributes['balance_type'];
        }

        $wallet->update($updateData);
    }

    /**
     * Create a new wallet for the student.
     *
     * @param Student $student
     * @param array $attributes
     * @return Wallet
     */
    private static function createNewWallet(Student $student, array $attributes): Wallet
    {
        $walletData = [
            'balance' => $attributes['balance'],
            'offer_id' => $attributes['offer_id'],
        ];

        // Set balance_type if column exists
        if (Schema::hasColumn('wallets', 'balance_type')) {
            if (isset($attributes['balance_type'])) {
                $walletData['balance_type'] = $attributes['balance_type'];
            } else {
                $walletData['balance_type'] = WalletBalanceTypeEnum::FULL->value;
            }
        }

        // Store installment tracking if this is an installment payment (guard columns)
        if (Schema::hasColumn('wallets', 'installment_no') && isset($attributes['installment_no'])) {
            $walletData['installment_no'] = $attributes['installment_no'];
        }
        if (Schema::hasColumn('wallets', 'total_tranches') && isset($attributes['total_tranches'])) {
            $walletData['total_tranches'] = $attributes['total_tranches'];
        }

        return $student->wallets()->create($walletData);
    }

    /**
     * Store the examen details for the student.
     *
     * @param Student $student
     * @param string $offer_id
     * @return void
     */
    private static function storeExamen(Student $student, string $offer_id): void
    {
        StoreExamenRepo::run($student, $offer_id);
    }
}
