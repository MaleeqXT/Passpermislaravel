<?php

namespace App\Http\Controllers\V1\EndPoint\Student;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\CompetencyEvaluationDetail;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()?->student;

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        $reservations = Reservation::query()
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->with(['training.offer', 'monitor.user'])
            ->orderByDesc('date')
            ->orderByDesc('start_at')
            ->get();

        // A student may have several monitors. The latest evaluation recorded
        // by any of their monitors is the value displayed in their booklet.
        $ratingsByCompetency = Rating::query()
            ->where('student_id', $student->id)
            ->with(['evaluationDetail', 'reservation', 'competency.mainCompetency'])
            ->get()
            ->sortByDesc(fn (Rating $rating) => ($rating->reservation?->date?->format('Y-m-d') ?? '') . ' ' . ($rating->reservation?->start_at?->format('H:i') ?? ''))
            ->unique('competency_id')
            ->keyBy('competency_id');
        $ratingsByReservation = Rating::query()
            ->where('student_id', $student->id)
            ->whereIn('reservation_id', $reservations->pluck('id'))
            ->with('competency.mainCompetency')
            ->get()
            ->groupBy('reservation_id');

        $competencies = MainCompetency::query()
            ->whereIn('label', ['C1', 'C2', 'C3', 'C4'])
            ->with(['competencies' => fn ($query) => $query
                ->where('status', 1)
                ->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->values()
            ->map(function ($mainCompetency, int $mainIndex) use ($ratingsByCompetency) {
                $questions = $mainCompetency->competencies
                    ->values()
                    ->map(function ($competency, int $questionIndex) use ($mainIndex, $ratingsByCompetency) {
                        $rating = $ratingsByCompetency->get($competency->id);
                        $level = min(5, max(0, (int) ($rating?->rating ?? 0)));

                        return [
                            'id' => (string) $competency->id,
                            'letter' => ($mainIndex + 1) . '.' . ($questionIndex + 1),
                            'title' => $competency->label,
                            'level' => $level,
                            'status' => $level >= 5 ? 'acquired' : ($level > 0 ? 'in_progress' : 'neutral'),
                            'observations' => $rating?->comment ?? '',
                            'studentResponse' => $rating?->evaluationDetail?->student_response ?? '',
                            'studentEvaluation' => $rating?->evaluationDetail?->student_evaluation ?? false,
                            'activeTab' => $rating?->evaluationDetail?->active_tab ?? 'pourquoi',
                        ];
                    });

                $total = $questions->count();

                return [
                    'id' => strtolower($mainCompetency->label),
                    'code' => $mainCompetency->label,
                    'title' => $mainCompetency->name,
                    'progress' => $total ? (int) round(($questions->sum('level') / ($total * 5)) * 100) : 0,
                    'questions' => $questions->all(),
                ];
            });

        $totalQuestions = $competencies->sum(fn ($competency) => count($competency['questions']));
        $levelTotal = $competencies->sum(fn ($competency) => collect($competency['questions'])->sum('level'));
        $plannedMinutes = $reservations->sum(fn ($reservation) => $this->minutes($reservation));
        $completedMinutes = $reservations
            ->filter(fn ($reservation) => $this->isCompleted($reservation))
            ->sum(fn ($reservation) => $this->minutes($reservation));
        $nextReservation = $reservations
            ->filter(fn ($reservation) => !$this->isCompleted($reservation))
            ->sortBy(fn ($reservation) => (string) $reservation->date . ' ' . $this->time($reservation->start_at))
            ->first();

        return response()->json(['data' => [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name ?? '',
            ],
            'overallPercentage' => $totalQuestions ? (int) round(($levelTotal / ($totalQuestions * 5)) * 100) : 0,
            'competencies' => $competencies->all(),
            'hours' => [
                'plannedMinutes' => $plannedMinutes,
                'completedMinutes' => $completedMinutes,
                'remainingMinutes' => max(0, $plannedMinutes - $completedMinutes),
            ],
            'nextAppointment' => $nextReservation ? $this->appointment($nextReservation) : null,
            'lessons' => $reservations->take(20)->map(fn ($reservation) => $this->lesson($reservation, $ratingsByReservation->get($reservation->id, collect())))->values(),
        ]]);
    }

    public function updateStudentComment(Request $request, Competency $competency): JsonResponse
    {
        $student = $request->user()?->student;

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        $validated = $request->validate([
            'student_response' => ['nullable', 'string', 'max:10000'],
            'student_evaluation' => ['nullable', 'boolean'],
            'active_tab' => ['nullable', 'in:pourquoi,comment,risques,influences'],
        ]);

        // Keep the student's comment attached to the monitor's most recent
        // evaluation for this question. If the question was not rated yet,
        // attach it to the student's latest monitor reservation.
        $rating = Rating::query()
            ->where('student_id', $student->id)
            ->where('competency_id', $competency->id)
            ->with('reservation')
            ->get()
            ->sortByDesc(fn (Rating $item) => ($item->reservation?->date?->format('Y-m-d') ?? '') . ' ' . ($item->reservation?->start_at?->format('H:i') ?? ''))
            ->first();

        if (!$rating) {
            $reservation = Reservation::query()
                ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
                ->latest('date')
                ->latest('start_at')
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'Aucun moniteur associé à cet élève.'], 422);
            }

            $rating = Rating::query()->create([
                'competency_id' => $competency->id,
                'student_id' => $student->id,
                'monitor_id' => $reservation->monitor_id,
                'reservation_id' => $reservation->id,
                'rating' => 0,
                'comment' => null,
            ]);
        }

        $detail = CompetencyEvaluationDetail::query()->updateOrCreate(
            ['rating_id' => $rating->id],
            [
                'student_evaluation' => $validated['student_evaluation'] ?? false,
                'student_response' => $validated['student_response'] ?? null,
                'active_tab' => $validated['active_tab'] ?? 'pourquoi',
            ],
        );

        return response()->json(['data' => [
            'studentResponse' => $detail->student_response ?? '',
            'studentEvaluation' => (bool) $detail->student_evaluation,
            'activeTab' => $detail->active_tab ?? 'pourquoi',
        ]]);
    }

    public function lessonDetail(Request $request, Reservation $reservation): JsonResponse
    {
        $student = $request->user()?->student;

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        $reservation->load([
            'training.offer',
            'monitor.user',
            'reviewMonitor',
            'latestComment',
            'evaluation',
        ]);

        if ((string) $reservation->training?->student_id !== (string) $student->id) {
            return response()->json(['message' => 'Leçon introuvable.'], 404);
        }

        $date = $reservation->date instanceof Carbon ? $reservation->date : Carbon::parse($reservation->date);
        $monitorName = $reservation->monitor?->user?->name ?? '';
        $evaluationData = is_array($reservation->evaluation?->data) ? $reservation->evaluation->data : [];
        $maneuvers = $evaluationData['manoeuvres'] ?? $evaluationData['maneuvers'] ?? [];

        if (!is_array($maneuvers)) {
            $maneuvers = array_filter([(string) $maneuvers]);
        }

        return response()->json(['data' => [
            'id' => (string) $reservation->id,
            'date' => $date->format('Y-m-d'),
            'duration' => $this->formatMinutes($this->minutes($reservation)),
            'startAt' => $this->time($reservation->start_at),
            'monitor' => $monitorName ?: null,
            'type' => $reservation->training?->session_type
                ?? $reservation->training?->prestation
                ?? $reservation->training?->offer?->name,
            'gearbox' => $this->gearboxLabel($student->boite_type),
            'evaluations' => [
                'initial' => $this->nullableBoolean($evaluationData['evaluation_initiale'] ?? $evaluationData['initial_evaluation'] ?? null),
                'permit' => $this->nullableBoolean($evaluationData['evaluation_permis'] ?? $evaluationData['permit_evaluation'] ?? null),
            ],
            'maneuvers' => array_values($maneuvers),
            'publicObservation' => $evaluationData['public_observations']
                ?? $reservation->latestComment?->comment
                ?? null,
            'teacherObservation' => $evaluationData['teacher_observations']
                ?? $evaluationData['observations_enseignant']
                ?? $reservation->reviewMonitor?->comment
                ?? null,
            'isAbsent' => (bool) $reservation->reviewMonitor?->is_absent,
        ]]);
    }

    private function appointment(Reservation $reservation): array
    {
        $date = $reservation->date instanceof Carbon ? $reservation->date : Carbon::parse($reservation->date);
        $monitor = $reservation->monitor?->user;

        return [
            'weekday' => strtoupper($date->locale('fr')->isoFormat('ddd')),
            'day' => $date->format('d'),
            'month' => strtoupper($date->locale('fr')->isoFormat('MMM')),
            'title' => $reservation->training?->offer?->name ?? 'Leçon de conduite',
            'date' => $date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'time' => $this->time($reservation->start_at),
            'monitorName' => $monitor?->name ?? '',
        ];
    }

    private function lesson(Reservation $reservation, $ratings): array
    {
        $date = $reservation->date instanceof Carbon ? $reservation->date : Carbon::parse($reservation->date);
        $monitor = $reservation->monitor?->user;
        $monitorName = $monitor?->name ?? '';

        return [
            'id' => (string) $reservation->id,
            'weekday' => strtoupper($date->locale('fr')->isoFormat('ddd')),
            'day' => $date->format('d'),
            'month' => strtoupper($date->locale('fr')->isoFormat('MMM')),
            'date' => $date->locale('fr')->isoFormat('D MMMM YYYY'),
            'time' => trim($this->time($reservation->start_at) . ' - ' . $this->time($reservation->end_at)),
            'type' => $reservation->training?->offer?->name ?? 'Conduite',
            'duration' => $this->formatMinutes($this->minutes($reservation)),
            'instructor' => $monitorName ?: '—',
            'initials' => collect(explode(' ', $monitorName))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''),
            'competencies' => $ratings->map(fn (Rating $rating) => $rating->competency?->mainCompetency?->label)->filter()->unique()->values()->map(fn ($code) => [$code, 'green'])->all(),
            'assessment' => '',
            'level' => (int) round($ratings->avg('rating') ?? 0),
        ];
    }

    private function minutes(Reservation $reservation): int
    {
        if ($reservation->start_at && $reservation->end_at) {
            return Carbon::parse($reservation->start_at)->diffInMinutes(Carbon::parse($reservation->end_at));
        }

        return (int) ($reservation->hour ?? 0) * 60;
    }

    private function isCompleted(Reservation $reservation): bool
    {
        $date = $reservation->date instanceof Carbon ? $reservation->date->format('Y-m-d') : $reservation->date;
        return Carbon::parse($date . ' ' . ($this->time($reservation->end_at) ?: '00:00'))->isPast();
    }

    private function time(mixed $value): string
    {
        return $value instanceof Carbon ? $value->format('H:i') : (string) ($value ?? '');
    }

    private function formatMinutes(int $minutes): string
    {
        return intdiv($minutes, 60) . 'h' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }

    private function gearboxLabel(mixed $boxType): ?string
    {
        if ($boxType === null || $boxType === '') {
            return null;
        }

        return in_array(strtolower((string) $boxType), ['1', 'automatic', 'automatique', 'auto', 'ba'], true)
            ? 'Boîte automatique'
            : 'Boîte manuelle';
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }
}
