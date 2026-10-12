<?php

namespace App\Services\Student\Training;

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps Formation lessons exact to the minute without changing the legacy
 * whole-hour wallet columns used by the rest of the application.
 */
class FormationWalletMinutesService
{
    public function assertAvailable(Student $student, string $offerId, int $minutes): void
    {
        $wallet = $this->lockedWallet($student, $offerId);
        abort_if($this->minutesFor($wallet) < $minutes, 422, 'Solde d’heures insuffisant pour ce forfait.');
    }

    public function debit(Student $student, string $offerId, int $minutes): Wallet
    {
        return DB::transaction(fn () => $this->adjust($student, $offerId, -$minutes));
    }

    public function credit(Student $student, string $offerId, int $minutes): Wallet
    {
        return DB::transaction(fn () => $this->adjust($student, $offerId, $minutes));
    }

    public function minutesFor(Wallet $wallet): int
    {
        return $wallet->balance_minutes === null
            ? max(0, (int) $wallet->balance * 60)
            : max(0, (int) $wallet->balance_minutes);
    }

    private function adjust(Student $student, string $offerId, int $delta): Wallet
    {
        $wallet = $this->lockedWallet($student, $offerId);
        $next = $this->minutesFor($wallet) + $delta;
        abort_if($next < 0, 422, 'Solde d’heures insuffisant pour ce forfait.');

        // balance remains a safe whole-hour fallback for legacy pages. Formation
        // reads balance_minutes, so a remaining 15 or 45 minutes is never lost.
        $wallet->update([
            'balance_minutes' => $next,
            'balance' => intdiv($next, 60),
        ]);
        $this->syncLegacyStudentBalance($student);

        return $wallet->fresh();
    }

    private function lockedWallet(Student $student, string $offerId): Wallet
    {
        abort_unless(Schema::hasColumn('wallets', 'balance_minutes'), 500, 'La migration des durées Formation doit être appliquée.');
        Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
        $wallet = Wallet::query()
            ->where('student_id', $student->id)
            ->where('offer_id', $offerId)
            ->where('status', WalletTypeEnum::ACTIVE->value)
            ->lockForUpdate()
            ->first();
        abort_unless($wallet, 422, 'Cet élève ne possède pas ce forfait actif.');

        if ($wallet->balance_minutes === null) {
            $wallet->update(['balance_minutes' => max(0, (int) $wallet->balance * 60)]);
            $wallet->refresh();
        }

        return $wallet;
    }

    private function syncLegacyStudentBalance(Student $student): void
    {
        $totalMinutes = Wallet::query()
            ->where('student_id', $student->id)
            ->where('status', WalletTypeEnum::ACTIVE->value)
            ->get(['balance', 'balance_minutes'])
            ->sum(fn (Wallet $wallet) => $this->minutesFor($wallet));

        Student::query()->whereKey($student->id)->update(['balance' => intdiv((int) $totalMinutes, 60)]);
    }
}