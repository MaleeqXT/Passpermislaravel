<?php

namespace App\Services\Monitor\Student;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Services\Student\Exam\StudentExamInformationService;

/**
 * Supplies only the colour-coding facts required by a monitor's student
 * profile. The caller must enforce monitor/student ownership before using it.
 */
class MonitorStudentProfileCodeService
{
    public function __construct(private readonly StudentExamInformationService $examInformation) {}

    /** @return array<string, array<string, int|string|bool|null>> */
    public function for(Monitor $monitor, Student $student): array
    {
        $exam = $this->examInformation->for($student);
        $presentationNumber = $this->presentationNumber($exam);
        $transmission = $this->transmission($monitor, $student);

        return [
            'transmission' => [
                'type' => $transmission,
                'label' => match ($transmission) {
                    'manual' => 'Boîte manuelle',
                    'automatic' => 'Boîte automatique',
                    default => 'Type de boîte non renseigné',
                },
            ],
            'exam' => [
                'has_history' => (bool) ($exam['has_exam_record'] ?? false),
                'completed_attempts' => (int) ($exam['attempts'] ?? 0),
                'presentation_number' => $presentationNumber,
                'presentation_label' => $presentationNumber === null
                    ? 'Présentation non renseignée'
                    : $this->presentationLabel($presentationNumber),
                'tone' => $presentationNumber === null
                    ? 'neutral'
                    : ($presentationNumber === 1 ? 'light' : 'dark'),
            ],
        ];
    }

    /** @param array<string, mixed> $exam */
    private function presentationNumber(array $exam): ?int
    {
        $attempts = max(0, (int) ($exam['attempts'] ?? 0));

        if (! empty($exam['next_exam'])) {
            return $attempts + 1;
        }

        return $attempts > 0 ? $attempts : null;
    }

    private function presentationLabel(int $number): string
    {
        return match ($number) {
            1 => '1ère présentation',
            2 => '2ème présentation',
            default => $number.'ème présentation',
        };
    }

    private function transmission(Monitor $monitor, Student $student): ?string
    {
        // 0 = boîte manuelle; 1 = boîte automatique.
        if (in_array($student->boite_type, [0, '0'], true)) {
            return 'manual';
        }

        if (in_array($student->boite_type, [1, '1'], true)) {
            return 'automatic';
        }

        // Older profiles can lack the preference. Fall back to a real lesson
        // tied to this monitor, first to its saved choice, then its offer.
        $reservation = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->with(['training.offer', 'evaluation'])
            ->orderByDesc('date')
            ->orderByDesc('start_at')
            ->first();

        $formation = $reservation?->evaluation?->data['formation'] ?? [];
        if (is_array($formation) && array_key_exists('boite_auto', $formation)) {
            return (bool) $formation['boite_auto'] ? 'automatic' : 'manual';
        }

        if ($reservation?->training?->offer && $reservation->training->offer->is_auto !== null) {
            return $reservation->training->offer->is_auto ? 'automatic' : 'manual';
        }

        return null;
    }
}
