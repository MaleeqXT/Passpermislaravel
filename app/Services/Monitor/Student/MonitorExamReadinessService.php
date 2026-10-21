<?php

namespace App\Services\Monitor\Student;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\MonitorExamReadiness;
use App\Models\MonitorExamReadinessNotification;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Notifications\V1\Monitor\Exam\StudentReadyForExamNotification;
use App\Services\Student\Exam\StudentExamInformationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Owns the monitor-only exam-preparation state. Exam appointments and results
 * are deliberately read from StudentExamInformationService, the same source
 * already used by the Student and staff exam flows.
 */
class MonitorExamReadinessService
{
    public function __construct(private readonly StudentExamInformationService $examInformation) {}

    /** @return array<string, mixed> */
    public function for(Student $student): array
    {
        $readiness = MonitorExamReadiness::query()
            ->where('student_id', $student->id)
            ->first();

        return [
            'preparation' => $this->readinessPayload($readiness),
            'exam' => $this->examPayload($this->examInformation->for($student)),
        ];
    }

    /**
     * @param array{is_ready: bool, additional_hours?: int|null, practice_frequency?: string|null, observation?: string|null} $attributes
     * @return array<string, mixed>
     */
    public function update(Monitor $monitor, Student $student, array $attributes): array
    {
        $result = DB::transaction(function () use ($monitor, $student, $attributes): array {
            $readiness = MonitorExamReadiness::query()
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            $exists = (bool) $readiness;
            $wasReady = (bool) $readiness?->is_ready;
            $readiness ??= new MonitorExamReadiness(['student_id' => $student->id]);

            $readiness->fill([
                'monitor_id' => $monitor->id,
                'is_ready' => (bool) $attributes['is_ready'],
                'additional_hours' => $attributes['additional_hours'] ?? null,
                'practice_frequency' => $attributes['practice_frequency'] ?? null,
                'observation' => $attributes['observation'] ?? null,
            ]);

            $hasRelevantChange = ! $exists || $readiness->isDirty([
                'is_ready',
                'additional_hours',
                'practice_frequency',
                'observation',
            ]);

            if (! $readiness->is_ready) {
                $readiness->ready_at = null;
            } elseif (! $wasReady) {
                $readiness->ready_at = now();
            }

            if ($readiness->is_ready && $hasRelevantChange) {
                $readiness->notification_sequence = ((int) $readiness->notification_sequence) + 1;
            }

            $readiness->save();

            $createdFor = collect();
            if ($readiness->is_ready && $hasRelevantChange) {
                $student->loadMissing('user');
                $stateHash = $this->stateHash($readiness);
                $data = $this->notificationData($student, $readiness);
                $secretaries = User::query()
                    ->isSecretary()
                    ->where('status', SituationStatusEnum::ACTIVE->value)
                    ->get();

                foreach ($secretaries as $secretary) {
                    $notification = MonitorExamReadinessNotification::query()->firstOrCreate(
                        [
                            'exam_readiness_id' => $readiness->id,
                            'secretary_user_id' => $secretary->id,
                            'state_hash' => $stateHash,
                        ],
                        ['notification_data' => $data],
                    );

                    if ($notification->wasRecentlyCreated) {
                        $createdFor->push($secretary);
                    }
                }
            }

            return [
                'readiness' => $readiness->fresh(),
                'secretaries' => $createdFor,
            ];
        });

        /** @var Collection<int, User> $secretaries */
        $secretaries = $result['secretaries'];
        $recipients = $secretaries->filter(fn (User $secretary) => filled($secretary->email));
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new StudentReadyForExamNotification(
                    $student->loadMissing('user'),
                    $result['readiness'],
                    $this->bookingPath($student),
                ),
            );
        }

        return [
            ...$this->for($student),
            'notification_created' => $secretaries->isNotEmpty(),
            'notification_count' => $secretaries->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function readinessPayload(?MonitorExamReadiness $readiness): array
    {
        return [
            'is_ready' => (bool) $readiness?->is_ready,
            'additional_hours' => $readiness?->additional_hours,
            'practice_frequency' => $readiness?->practice_frequency,
            'observation' => $readiness?->observation,
            'ready_at' => $readiness?->ready_at?->toIso8601String(),
            'updated_at' => $readiness?->updated_at?->toIso8601String(),
        ];
    }

    /** @param array<string, mixed> $examInformation
     * @return array<string, string|null> */
    private function examPayload(array $examInformation): array
    {
        $next = $examInformation['next_exam'] ?? null;
        $latest = $examInformation['latest_exam'] ?? null;
        $current = $next ?: $latest;
        $result = $current['result'] ?? null;
        $status = $current['status'] ?? null;

        $isBooked = (bool) $next && ($next['status'] ?? null) === 'pending';
        $isFinal = in_array($status, ['success', 'failed'], true)
            || in_array($result, ['admitted', 'refused'], true);

        return [
            'status' => $isBooked ? 'booked' : ($isFinal ? 'completed' : 'waiting'),
            'status_label' => $isBooked
                ? 'Réservé à l’examen'
                : ($isFinal ? 'Terminé' : 'En attente'),
            'date' => $current['date'] ?? null,
            'result' => match ($result) {
                'admitted' => 'passed',
                'refused' => 'failed',
                default => 'pending',
            },
            'result_label' => match ($result) {
                'admitted' => 'Réussi',
                'refused' => 'Échoué',
                default => 'En attente',
            },
        ];
    }

    private function stateHash(MonitorExamReadiness $readiness): string
    {
        return hash('sha256', json_encode([
            'sequence' => (int) $readiness->notification_sequence,
            'ready' => (bool) $readiness->is_ready,
            'additional_hours' => $readiness->additional_hours,
            'practice_frequency' => $readiness->practice_frequency,
            'observation' => $readiness->observation,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, mixed> */
    private function notificationData(Student $student, MonitorExamReadiness $readiness): array
    {
        $name = trim(implode(' ', array_filter([
            $student->user?->first_name,
            $student->user?->last_name,
        ]))) ?: ($student->user?->name ?: 'Élève');

        return [
            'type' => 'exam_readiness',
            'title' => 'Élève prêt pour l’examen',
            'message' => 'Élève prêt pour l’examen : '.$name,
            'student' => ['id' => $student->id, 'name' => $name],
            'preparation' => [
                'additional_hours' => $readiness->additional_hours,
                'practice_frequency' => $readiness->practice_frequency,
                'observation' => $readiness->observation,
            ],
            'booking_path' => $this->bookingPath($student),
        ];
    }

    private function bookingPath(Student $student): string
    {
        // /exams is the existing Secretariat exam-booking page in the React
        // application. The identifier lets that workflow focus the candidate.
        return '/exams?student_id='.rawurlencode((string) $student->id);
    }
}
