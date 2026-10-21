<?php

namespace App\Services\Monitor\Student;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Services\Student\Training\FormationWalletMinutesService;
use Carbon\Carbon;

/**
 * Builds the formation counters shown in the monitor's student fiche.
 *
 * The wallet is debited when a lesson is reserved, so its minute balance is
 * already the amount still available to schedule. Planned lessons must never
 * be subtracted from that balance a second time.
 */
class MonitorStudentHoursService
{
    public function __construct(private readonly FormationWalletMinutesService $walletMinutes) {}

    /** @return array<string, array<string, int|null>> */
    public function for(Monitor $monitor, Student $student): array
    {
        $completedMinutes = 0;
        $plannedMinutes = 0;
        $absenceMinutes = 0;
        $now = now();

        Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($training) => $training->where('student_id', $student->id))
            ->with(['training.cancellation', 'reviewMonitor', 'evaluation'])
            ->orderBy('date')
            ->orderBy('start_at')
            ->get()
            ->each(function (Reservation $reservation) use (&$completedMinutes, &$plannedMinutes, &$absenceMinutes, $now): void {
                if ($this->isCancelled($reservation)) {
                    return;
                }

                $minutes = $this->durationMinutes($reservation);
                $data = $reservation->evaluation?->data ?? [];
                $absent = (bool) ($data['formation']['is_absent'] ?? $reservation->reviewMonitor?->is_absent ?? false);
                $start = $this->at($reservation, 'start_at');
                $end = $this->at($reservation, 'end_at');

                // Absence is an outcome: it only contributes once its lesson
                // has ended and never contributes to completed driving hours.
                if ($end->lessThanOrEqualTo($now)) {
                    if ($absent) {
                        $absenceMinutes += $minutes;
                    } else {
                        $completedMinutes += $minutes;
                    }

                    return;
                }

                // An absence cannot be a future planned driving lesson.
                if (! $absent && $start->greaterThanOrEqualTo($now)) {
                    $plannedMinutes += $minutes;
                }
            });

        $remainingMinutes = $student->wallets()
            ->where('status', WalletTypeEnum::ACTIVE->value)
            ->get()
            ->sum(fn ($wallet) => $this->walletMinutes->minutesFor($wallet));

        return [
            'initial_evaluation' => [
                'estimated_hours' => $this->estimatedHours($monitor, $student),
            ],
            'hours' => [
                'completed_minutes' => $completedMinutes,
                'planned_minutes' => $plannedMinutes,
                'remaining_minutes' => (int) $remainingMinutes,
                'absence_minutes' => $absenceMinutes,
                'consumed_minutes' => $completedMinutes + $absenceMinutes,
            ],
        ];
    }

    private function estimatedHours(Monitor $monitor, Student $student): ?int
    {
        $evaluations = DocumentEvaluation::query()
            ->where('student_id', $student->id)
            ->where('monitor_id', $monitor->id)
            ->orderByDesc('updated_at')
            ->get(['data']);

        foreach ($evaluations as $evaluation) {
            $data = $evaluation->data ?? [];
            $value = $data['initial_evaluation_questions']['final']['lessons_proposed']
                ?? $data['results']['lessons_proposed']
                ?? null;

            if ($value !== null && $value !== '' && is_numeric($value)) {
                return max(0, (int) $value);
            }
        }

        return null;
    }

    private function isCancelled(Reservation $reservation): bool
    {
        return ! $reservation->is_active
            || (int) $reservation->training?->cancellation?->status === CancellationStatusEnum::SUCCESS_CANCELLED->value;
    }

    private function durationMinutes(Reservation $reservation): int
    {
        return $reservation->duration_minutes === null
            ? max(0, (int) $reservation->hour * 60)
            : max(0, (int) $reservation->duration_minutes);
    }

    private function at(Reservation $reservation, string $timeColumn): Carbon
    {
        return Carbon::parse($reservation->date->toDateString().' '.$reservation->{$timeColumn}->format('H:i'));
    }
}
