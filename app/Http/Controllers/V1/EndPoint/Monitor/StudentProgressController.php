<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\CompetencyEvaluationDetail;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use App\Models\Roles\Student\User\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function show(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['message' => 'Moniteur introuvable.'], 404);
        }

        $reservations = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->with(['training.offer', 'monitor.user'])
            ->orderByDesc('date')
            ->orderByDesc('start_at')
            ->get();

        // Do not expose a student's progress to a monitor who has never had a
        // reservation with that student.
        if ($reservations->isEmpty()) {
            return response()->json(['message' => 'Ce candidat n’est pas lié à ce moniteur.'], 403);
        }

        $ratings = Rating::query()
            ->where('student_id', $student->id)
            ->where('monitor_id', $monitor->id)
            ->with(['evaluationDetail', 'reservation', 'competency.mainCompetency'])
            ->get();

        // The current booklet uses the most recent lesson's rating for each
        // question, while the complete rating remains attached to its class.
        $ratingsByCompetency = $ratings
            ->sortByDesc(fn (Rating $rating) => ($rating->reservation?->date?->format('Y-m-d') ?? '') . ' ' . ($rating->reservation?->start_at?->format('H:i') ?? ''))
            ->unique('competency_id')
            ->keyBy('competency_id');
        $ratingsByReservation = $ratings->filter(fn (Rating $rating) => $rating->reservation_id)->groupBy('reservation_id');

        // Ratings belong to both a student and a monitor, so only load the
        // selected monitor's evaluation for this candidate.
        $competencies = MainCompetency::query()
            ->whereIn('label', ['C1', 'C2', 'C3', 'C4'])
            ->with(['competencies' => fn ($query) => $query->where('status', 1)->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->values()
            ->take(4)
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
                            'lessonCount' => 0,
                            'observations' => $rating?->comment ?? '',
                            'studentEvaluation' => $rating?->evaluationDetail?->student_evaluation ?? false,
                            'studentResponse' => $rating?->evaluationDetail?->student_response ?? '',
                            'activeTab' => $rating?->evaluationDetail?->active_tab ?? 'pourquoi',
                        ];
                    });

                $total = $questions->count();
                $levelTotal = $questions->sum('level');

                return [
                    'id' => 'c' . ($mainIndex + 1),
                    'code' => 'C' . ($mainIndex + 1),
                    'title' => $mainCompetency->name,
                    'progress' => $total ? (int) round(($levelTotal / ($total * 5)) * 100) : 0,
                    'questions' => $questions->all(),
                ];
            });

        $plannedMinutes = $reservations->sum(fn ($reservation) => $this->minutes($reservation));
        $completedMinutes = $reservations
            ->filter(fn ($reservation) => $this->isCompleted($reservation))
            ->sum(fn ($reservation) => $this->minutes($reservation));

        $totalQuestions = $competencies->sum(fn ($competency) => count($competency['questions']));
        $levelTotal = $competencies->sum(fn ($competency) => collect($competency['questions'])->sum('level'));

        return response()->json([
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user?->name ?? trim(($student->user?->first_name ?? '') . ' ' . ($student->user?->last_name ?? '')),
                ],
                'overallPercentage' => $totalQuestions ? (int) round(($levelTotal / ($totalQuestions * 5)) * 100) : 0,
                'competencies' => $competencies->all(),
                'hours' => [
                    'plannedMinutes' => $plannedMinutes,
                    'completedMinutes' => $completedMinutes,
                    'remainingMinutes' => max(0, $plannedMinutes - $completedMinutes),
                ],
                'lessons' => $reservations->take(20)->map(function ($reservation) use ($ratingsByReservation) {
                    $date = $reservation->date instanceof Carbon ? $reservation->date : Carbon::parse($reservation->date);
                    $duration = $this->minutes($reservation);

                    return [
                        'id' => (string) $reservation->id,
                        'weekday' => strtoupper($date->locale('fr')->isoFormat('ddd')),
                        'day' => $date->format('d'),
                        'month' => strtoupper($date->locale('fr')->isoFormat('MMM')),
                        'date' => $date->locale('fr')->isoFormat('D MMMM YYYY'),
                        'time' => trim($this->time($reservation->start_at) . ' - ' . $this->time($reservation->end_at)),
                        'type' => $reservation->training?->offer?->name ?? 'Conduite',
                        'duration' => $this->formatMinutes($duration),
                        'skills' => $ratingsByReservation->get($reservation->id, collect())->map(fn (Rating $rating) => $rating->competency?->mainCompetency?->label)->filter()->unique()->values()->all(),
                        'report' => '',
                        'level' => (int) round($ratingsByReservation->get($reservation->id, collect())->avg('rating') ?? 0),
                    ];
                })->values(),
            ],
        ]);
    }

    public function updateEvaluation(Request $request, Student $student, Competency $competency): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:0', 'max:5'],
            'observations' => ['nullable', 'string', 'max:10000'],
            'student_evaluation' => ['nullable', 'boolean'],
            'student_response' => ['nullable', 'string', 'max:10000'],
            'active_tab' => ['nullable', 'in:pourquoi,comment,risques,influences'],
            'reservation_id' => ['nullable', 'uuid'],
        ]);

        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['message' => 'Moniteur introuvable.'], 404);
        }

        $isLinkedToMonitor = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->exists();

        if (!$isLinkedToMonitor) {
            return response()->json(['message' => 'Ce candidat n’est pas lié à ce moniteur.'], 403);
        }

        $reservationQuery = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id));
        $reservation = !empty($validated['reservation_id'])
            ? (clone $reservationQuery)->whereKey($validated['reservation_id'])->first()
            : (clone $reservationQuery)->where(function ($query) {
                $query->whereDate('date', '<', now()->toDateString())
                    ->orWhere(function ($today) {
                        $today->whereDate('date', now()->toDateString())->whereTime('end_at', '<=', now()->format('H:i'));
                    });
            })->orderByDesc('date')->orderByDesc('start_at')->first()
                ?? (clone $reservationQuery)->orderByDesc('date')->orderByDesc('start_at')->first();

        if (!$reservation) {
            return response()->json(['message' => 'Aucune leçon disponible pour enregistrer cette évaluation.'], 422);
        }

        $rating = Rating::query()->updateOrCreate(
            [
                'competency_id' => $competency->id,
                'student_id' => $student->id,
                'monitor_id' => $monitor->id,
                'reservation_id' => $reservation->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['observations'] ?? null,
            ],
        );

        CompetencyEvaluationDetail::query()->updateOrCreate(
            ['rating_id' => $rating->id],
            [
                'student_evaluation' => $validated['student_evaluation'] ?? false,
                'student_response' => $validated['student_response'] ?? null,
                'active_tab' => $validated['active_tab'] ?? 'pourquoi',
            ],
        );

        return $this->show($request, $student);
    }

    private function resolveMonitor(Request $request): ?Monitor
    {
        $user = $request->user();
        $monitor = $user?->monitor ?? Monitor::query()->where('user_id', $user?->id)->first();

        if (!$monitor && $request->filled('monitor_id') && $user?->hasAnyRole(['admin', 'super-admin'])) {
            $monitor = Monitor::query()
                ->whereKey($request->input('monitor_id'))
                ->orWhere('user_id', $request->input('monitor_id'))
                ->first();
        }

        return $monitor;
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
}
