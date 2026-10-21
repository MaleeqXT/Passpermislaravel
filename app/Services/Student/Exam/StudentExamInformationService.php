<?php

namespace App\Services\Student\Exam;

use App\Enums\V2\Student\Examen\ExamenResultPermisEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Exam\StudentExam;
use App\Models\Roles\Student\User\Student;
use Carbon\Carbon;

/**
 * Builds the examination summary used by the student dashboard and by
 * monitor-scoped student-profile endpoints.  Keeping it here guarantees that
 * historical reservation de-duplication remains identical for both roles.
 */
class StudentExamInformationService
{
    /** @return array<string, mixed> */
    public function for(Student $student): array
    {
        $examQuery = StudentExam::query()->where('student_id', $student->id);

        // Older appointments are stored as lessons. Read both data stores so
        // no historic driving-exam passage is lost.
        $reservationQuery = Reservation::query()
            ->with([
                'training:id,student_id,reservation_id,session_type,prestation',
                'lieu:id,zone_id,name',
                'lieu.zone:id,name',
                'monitor.user:id,name,first_name,last_name',
                'reviewMonitor:id,reservation_id,is_absent',
            ])
            ->where('is_active', true)
            ->whereHas('training', function ($query) use ($student) {
                $query
                    ->where('student_id', $student->id)
                    ->where(function ($examQuery) {
                        $examQuery
                            ->where('session_type', 'like', '%EXA%')
                            ->orWhere('session_type', 'like', '%exam%')
                            ->orWhere('prestation', 'like', '%EXA%')
                            ->orWhere('prestation', 'like', '%exam%');
                    });
            });

        $relations = [
            'lieu:id,zone_id,name',
            'lieu.zone:id,name',
            'monitor.user:id,name,first_name,last_name',
        ];

        $nextScheduledExam = (clone $examQuery)
            ->with($relations)
            ->whereNotNull('date_examen')
            ->whereDate('date_examen', '>=', today())
            ->orderBy('date_examen')
            ->orderBy('heure_passage')
            ->first();

        $nextExam = (clone $examQuery)
            ->with($relations)
            ->whereNotNull('date_examen')
            ->whereDate('date_examen', '>=', today())
            ->whereNull('result_permis')
            ->where('status', ExamenStatusEnum::PENDING->value)
            ->orderBy('date_examen')
            ->orderBy('heure_passage')
            ->first();

        $nextExam ??= $nextScheduledExam;

        $firstExam = (clone $examQuery)
            ->with($relations)
            ->whereNotNull('date_examen')
            ->orderBy('date_examen')
            ->orderBy('heure_passage')
            ->first();

        $nextReservation = (clone $reservationQuery)
            ->isComme()
            ->orderBy('date')
            ->orderBy('start_at')
            ->first();

        $firstReservation = (clone $reservationQuery)
            ->orderBy('date')
            ->orderBy('start_at')
            ->first();

        $pastReservations = (clone $reservationQuery)
            ->isPassed()
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();

        $latestExam = (clone $examQuery)
            ->with($relations)
            ->orderByDesc('date_examen')
            ->orderByDesc('created_at')
            ->first();

        $finalizedExamRecords = (clone $examQuery)
            ->where(function ($query) {
                $query
                    ->whereNotNull('result_permis')
                    ->orWhereIn('status', [
                        ExamenStatusEnum::SUCCESS->value,
                        ExamenStatusEnum::FAILED->value,
                    ]);
            })
            ->get(['id', 'date_examen', 'status', 'result_permis']);

        $attemptedExamRecords = (clone $examQuery)
            ->where(function ($query) {
                $query
                    ->whereNotNull('result_permis')
                    ->orWhereIn('status', [
                        ExamenStatusEnum::SUCCESS->value,
                        ExamenStatusEnum::FAILED->value,
                    ])
                    ->orWhereDate('date_examen', '<', today())
                    ->orWhere(function ($todayQuery) {
                        $todayQuery
                            ->whereDate('date_examen', today())
                            ->whereTime('heure_passage', '<=', now()->format('H:i:s'));
                    });
            })
            ->get(['id', 'date_examen', 'status', 'result_permis']);

        $reservationDates = $pastReservations
            ->map(fn (Reservation $reservation) => $reservation->date?->toDateString())
            ->filter()
            ->unique();

        $unmatchedAttempts = $attemptedExamRecords
            ->filter(fn (StudentExam $exam) => ! $exam->date_examen || ! $reservationDates->contains((string) $exam->date_examen));

        $unmatchedCompleted = $finalizedExamRecords
            ->filter(fn (StudentExam $exam) => ! $exam->date_examen || ! $reservationDates->contains((string) $exam->date_examen));

        $attempts = $pastReservations->count() + $unmatchedAttempts->count();
        $completedExams = $pastReservations
            ->reject(fn (Reservation $reservation) => $reservation->reviewMonitor?->is_absent === true)
            ->count() + $unmatchedCompleted->count();

        $successfulExams = (clone $examQuery)
            ->where(function ($query) {
                $query
                    ->where('result_permis', ExamenResultPermisEnum::ADMIS->value)
                    ->orWhere('status', ExamenStatusEnum::SUCCESS->value);
            })
            ->count();

        $knownOutcomes = $finalizedExamRecords->count();
        $successRate = $knownOutcomes > 0
            ? (int) round((min($successfulExams, $knownOutcomes) / $knownOutcomes) * 100)
            : null;

        $nextExamData = collect([
            $nextExam ? $this->formatExam($nextExam) : null,
            $nextReservation ? $this->formatReservation($nextReservation) : null,
        ])->filter()->sortBy(fn (array $exam) => ($exam['date'] ?? '').' '.($exam['time'] ?? ''))->first();

        $firstExamData = collect([
            $firstExam ? $this->formatExam($firstExam) : null,
            $firstReservation ? $this->formatReservation($firstReservation) : null,
        ])->filter()->sortBy(fn (array $exam) => ($exam['date'] ?? '').' '.($exam['time'] ?? ''))->first();

        $latestExamData = $latestExam
            ? $this->formatExam($latestExam)
            : ($pastReservations->last() ? $this->formatReservation($pastReservations->last()) : null);

        return [
            'registration_date' => $student->created_at?->toDateString(),
            'has_exam_record' => (clone $examQuery)->exists() || (clone $reservationQuery)->exists(),
            'next_exam' => $nextExamData,
            'first_exam' => $firstExamData,
            'latest_exam' => $latestExamData,
            'completed_exams' => $completedExams,
            'attempts' => $attempts,
            'successful_exams' => $successfulExams,
            'estimated_success_rate' => $successRate,
            'mock_exams' => [],
        ];
    }

    /** @return array<string, string|null> */
    private function formatReservation(Reservation $reservation): array
    {
        $monitorUser = $reservation->monitor?->user;
        $monitorName = $monitorUser?->name
            ?: trim(implode(' ', array_filter([
                $monitorUser?->first_name,
                $monitorUser?->last_name,
            ])));
        $date = $reservation->date?->toDateString();
        $time = $reservation->start_at?->format('H:i');
        $endAt = $date && $reservation->end_at
            ? Carbon::parse($date.' '.$reservation->end_at->format('H:i:s'))
            : null;

        return [
            'id' => $reservation->id,
            'date' => $date,
            'time' => $time,
            'location' => $reservation->lieu?->name,
            'zone' => $reservation->lieu?->zone?->name,
            'monitor_name' => $monitorName ?: null,
            'comment' => null,
            'status' => $endAt?->isPast() ? 'completed' : 'pending',
            'result' => null,
            'source' => 'reservation',
        ];
    }

    /** @return array<string, string|null> */
    private function formatExam(StudentExam $exam): array
    {
        $monitorUser = $exam->monitor?->user;
        $monitorName = $monitorUser?->name
            ?: trim(implode(' ', array_filter([
                $monitorUser?->first_name,
                $monitorUser?->last_name,
            ])));

        return [
            'id' => $exam->id,
            'date' => $exam->date_examen ? (string) $exam->date_examen : null,
            'time' => $exam->heure_passage?->format('H:i'),
            'location' => $exam->lieu?->name,
            'zone' => $exam->lieu?->zone?->name,
            'monitor_name' => $monitorName ?: null,
            'comment' => $exam->comment,
            'status' => $this->statusLabel((int) $exam->status),
            'result' => $exam->result_permis === null
                ? null
                : $this->resultLabel((int) $exam->result_permis),
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            ExamenStatusEnum::SUCCESS->value => 'success',
            ExamenStatusEnum::FAILED->value => 'failed',
            default => 'pending',
        };
    }

    private function resultLabel(int $result): string
    {
        return match ($result) {
            ExamenResultPermisEnum::ADMIS->value => 'admitted',
            ExamenResultPermisEnum::REFUS->value => 'refused',
            default => 'pending',
        };
    }
}
