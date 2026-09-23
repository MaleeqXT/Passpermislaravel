<?php

namespace App\Repository\V2\Student\Schedule\Training\Wallet;

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletBalanceTypeEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class IncOrDecWalletRepo
{
    /**
     * @param Student|string $student
     * @param string $offer_id
     * @param string $status
     * @param int $wallet
     * @return Model|Builder
     */
    public static function run(Student|string $student, string $offer_id, string $status = 'inc', int $wallet = 1): Model|Builder
    {
        $objEleve = self::getStudentInstance($student);
        $value = $status === 'inc' ? $wallet : -$wallet;

        $wallet = self::getStudentWallet($objEleve, $offer_id);
        $newBalance = self::calculateNewBalance($objEleve, $value);

        if (!$wallet) {
            self::createWallet($objEleve, $offer_id, $value);
        } else {
            // Always update wallet balance if it exists
            self::updateWalletBalance($wallet, $value);
        }

        // Update student's total balance
        $objEleve->update(['balance' => $newBalance]);

        return $objEleve;
    }

    /**
     * Get the Student instance, either from the provided ID or directly if an instance is passed.
     *
     * @param Student|string $student
     * @return Student
     */
    private static function getStudentInstance(Student|string $student): Student
    {
        if (!$student instanceof Model) {
            return Student::query()->findOrFail($student);
        }
        return $student;
    }

    /**
     * Get the wallet for the student and offer ID.
     *
     * @param Student $student
     * @param string $offer_id
     * @return Wallet|null
     */
    private static function getStudentWallet(Student $student, string $offer_id)
    {
        // Try direct match first
        $wallet = $student->wallets()->where('offer_id', $offer_id)->first();
        if ($wallet) return $wallet;

        // Fallback: try to find a wallet whose related offer has the given id
        // This handles cases where offer_id types may differ (int vs uuid)
        return $student->wallets()->whereHas('offer', function ($q) use ($offer_id) {
            $q->where('id', $offer_id);
        })->first();
    }

    /**
     * Calculate the new balance for the student.
     *
     * @param Student $student
     * @param int $value
     * @return int
     */
    private static function calculateNewBalance(Student $student, int $value): int
    {
        $newBalance = ($student->balance ?? 0) + $value;
        return max($newBalance, 0); // Ensure balance is not negative
    }

    /**
     * Create a new wallet for the student if none exists.
     *
     * @param Student $student
     * @param string $offer_id
     * @param int $value
     * @return void
     */
    private static function createWallet(Student $student, string $offer_id, int $value): void
    {
        $walletData = [
            'student_id' => $student->id,
            'offer_id' => $offer_id,
            'balance' => max($value, 0),
            'status' => WalletTypeEnum::ACTIVE->value
        ];

        // Add balance_type if column exists
        if (Schema::hasColumn('wallets', 'balance_type')) {
            $walletData['balance_type'] = WalletBalanceTypeEnum::FULL->value;
        }

        Wallet::query()->create($walletData);
    }

    /**
     * Update the wallet balance.
     *
     * @param Wallet $wallet
     * @param int $value
     * @return void
     */
    private static function updateWalletBalance(Wallet $wallet, int $value): void
    {
        $newBalance = max(intval($wallet->balance ?? 0) + $value, 0);
        $wallet->update(['balance' => $newBalance]);
    }
}
