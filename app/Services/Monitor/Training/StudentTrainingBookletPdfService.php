<?php

namespace App\Services\Monitor\Training;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentTrainingBookletPdfService
{
    /** @param Collection<int, Reservation> $lessons */
    public function output(Student $student, Collection $lessons): string
    {
        $rows = $lessons->values()->map(fn (Reservation $lesson, int $index) => $this->row($lesson, $index + 1));
        $totalMinutes = $lessons->sum(fn (Reservation $lesson) => (int) ($lesson->duration_minutes ?: ((int) $lesson->hour * 60)));

        return Pdf::loadView('pdf.monitor.student-training-booklet', [
            'student' => $student,
            'rows' => $rows,
            'totalMinutes' => $totalMinutes,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->output();
    }

    public function filename(Student $student): string
    {
        return 'livret-formation-'.(Str::slug($student->user?->name ?: 'eleve') ?: 'eleve').'.pdf';
    }

    private function row(Reservation $lesson, int $number): array
    {
        $minutes = (int) ($lesson->duration_minutes ?: ((int) $lesson->hour * 60));
        $location = collect([$lesson->lieu?->zone?->name, $lesson->lieu?->name])->filter()->implode(' — ') ?: 'Non renseigné';

        return [
            'number' => $number,
            'date' => $lesson->date?->format('d/m/Y') ?? 'Non renseignée',
            'start' => $lesson->start_at?->format('H:i') ?? '—',
            'end' => $lesson->end_at?->format('H:i') ?? '—',
            'duration' => $this->durationLabel($minutes),
            'instructor' => $lesson->monitor?->user?->name ?? 'Non renseigné',
            'offer' => $lesson->training?->offer?->name ?? 'Non renseigné',
            'location' => $location,
            'type' => $lesson->training?->session_type ?: 'Prestation',
        ];
    }

    private function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        if (!$remaining) return $hours.' h';
        return $hours ? $hours.' h '.str_pad((string) $remaining, 2, '0', STR_PAD_LEFT) : $remaining.' min';
    }
}