<?php

namespace App\Http\Controllers\Theory;

use App\Http\Controllers\Controller;
use App\Models\Theory\TheoryAttemptAnswer;
use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheorySeries;
use App\Models\Theory\TheoryStudentProgress;
use App\Models\Theory\TheoryTestAttempt;
use App\Services\Theory\TheoryCodeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TheoryStudentController extends Controller
{
    public function __construct(private readonly TheoryCodeAccessService $codeAccess)
    {
    }

    /** A lightweight endpoint used only to decide whether the sidebar shows Code. */
    public function access(Request $request): JsonResponse
    {
        $student = $this->student($request);

        return response()->json(['data' => ['has_access' => $this->codeAccess->hasAccess($student)]]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $student = $this->studentWithAccess($request);
        $progress = TheoryStudentProgress::query()->where('student_id', $student->id)->get()->keyBy('category_id');
        $categories = TheoryCategory::query()
            ->where('is_active', true)
            ->withCount(['questions as available_questions_count' => fn ($query) => $query->where('status', 'published')->where('is_active', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(function (TheoryCategory $category) use ($progress) {
                $item = $progress->get($category->id);
                $answered = (int) ($item?->questions_answered ?? 0);
                $correct = (int) ($item?->correct_answers ?? 0);

                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'available_questions_count' => $category->available_questions_count,
                    'answered' => $answered,
                    'correct' => $correct,
                    'success_rate' => $answered ? (int) round(($correct / $answered) * 100) : null,
                ];
            });

        $attempts = TheoryTestAttempt::query()
            ->with('series:id,title,type')
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (TheoryTestAttempt $attempt) => $this->attemptSummary($attempt));

        $series = TheorySeries::query()
            ->withCount('seriesQuestions')
            ->where('status', 'published')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (TheorySeries $item) => $item->series_questions_count >= 40)
            ->values();
        $seriesAttempts = TheoryTestAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->whereIn('series_id', $series->pluck('id'))
            ->latest('submitted_at')
            ->get()
            ->groupBy('series_id');
        $catalogue = $series->map(function (TheorySeries $item) use ($seriesAttempts) {
            $history = $seriesAttempts->get($item->id, collect());
            $latest = $history->first();

            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'type' => $item->type,
                'duration_minutes' => $item->duration_minutes,
                'questions_count' => 40,
                'question_pool_count' => $item->series_questions_count,
                'attempt_count' => $history->count(),
                'last_score' => $latest?->score,
                'last_passed' => $latest?->passed,
            ];
        });

        $answered = (int) TheoryStudentProgress::query()->where('student_id', $student->id)->sum('questions_answered');
        $correct = (int) TheoryStudentProgress::query()->where('student_id', $student->id)->sum('correct_answers');
        $openAttempt = TheoryTestAttempt::query()
            ->with('series:id,title,type')
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        return response()->json(['data' => [
            'published_question_count' => TheoryQuestion::query()->where('status', 'published')->where('is_active', true)->count(),
            'answered_questions' => $answered,
            'average_success_rate' => $answered ? (int) round(($correct / $answered) * 100) : null,
            'tests_completed' => $attempts->where('mode', 'exam')->count(),
            'categories' => $categories,
            'training_series' => $catalogue->where('type', 'training')->values(),
            'exam_series' => $catalogue->where('type', 'exam')->values(),
            'attempts' => $attempts->values(),
            'open_attempt' => $openAttempt ? [
                'id' => $openAttempt->id,
                'mode' => $openAttempt->mode,
                'series_title' => $openAttempt->series?->title,
                'total_questions' => $openAttempt->total_questions,
                'started_at' => $openAttempt->started_at?->toIso8601String(),
                'expires_at' => $openAttempt->expires_at?->toIso8601String(),
            ] : null,
        ]]);
    }

    public function start(Request $request): JsonResponse
    {
        $student = $this->studentWithAccess($request);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['training', 'exam', 'mock'])],
            'series_id' => ['nullable', 'uuid'],
            'question_count' => ['nullable', 'integer', 'in:40'],
        ]);
        if (in_array($data['mode'], ['training', 'exam'], true) && empty($data['series_id'])) {
            throw ValidationException::withMessages(['series_id' => 'Choisissez une série pour commencer.']);
        }

        $openAttempt = TheoryTestAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();
        if ($openAttempt) {
            return response()->json([
                'message' => 'Une série non terminée existe déjà. Reprenez-la avant d’en démarrer une autre.',
                'data' => $this->openAttempt($openAttempt),
            ]);
        }

        $series = null;
        if ($data['mode'] === 'mock') {
            $questions = TheoryQuestion::query()
                ->with('options:id,question_id,label,sort_order,is_correct')
                ->where('status', 'published')
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit(40)
                ->get();
        } else {
            $series = TheorySeries::query()
                ->with(['seriesQuestions' => fn ($query) => $query->orderBy('sort_order'), 'seriesQuestions.question.options'])
                ->whereKey($data['series_id'])
                ->where('type', $data['mode'])
                ->where('status', 'published')
                ->firstOrFail();
            $pool = $series->seriesQuestions
                ->map(fn ($assignment) => $assignment->question)
                ->filter(fn ($question) => $question && $question->status === 'published' && $question->is_active)
                ->values();
            $seenQuestionIds = TheoryAttemptAnswer::query()
                ->whereHas('attempt', fn ($query) => $query->where('student_id', $student->id))
                ->whereIn('question_id', $pool->pluck('id'))
                ->pluck('question_id')
                ->unique()
                ->all();
            $unseen = $pool->reject(fn ($question) => in_array($question->id, $seenQuestionIds, true))->values();
            $questions = ($unseen->count() >= 40 ? $unseen : $pool)->shuffle()->take(40)->values();
        }

        if ($questions->count() !== 40) {
            throw ValidationException::withMessages(['series_id' => 'Cette série doit contenir au moins 40 questions publiées et actives avant de pouvoir être lancée.']);
        }

        $attempt = DB::transaction(function () use ($student, $questions, $series, $data) {
            $attempt = TheoryTestAttempt::create([
                'student_id' => $student->id,
                'series_id' => $series?->id,
                'mode' => $data['mode'],
                'status' => 'in_progress',
                'total_questions' => 40,
                'pass_threshold' => $data['mode'] === 'training' ? 0 : 35,
                'started_at' => now(),
                'expires_at' => $data['mode'] === 'exam'
                    ? now()->addMinutes((int) ($series?->duration_minutes ?: 40))
                    : null,
            ]);

            foreach ($questions->values() as $index => $question) {
                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'sort_order' => $index + 1,
                    'question_snapshot' => $this->snapshot($question),
                ]);
            }

            return $attempt;
        });

        return response()->json(['data' => $this->openAttempt($attempt)], 201);
    }

    public function show(Request $request, TheoryTestAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);
        if ($attempt->status === 'in_progress' && $this->hasExpired($attempt)) {
            $attempt = $this->finalizeAttempt($attempt);
        }

        return response()->json(['data' => $attempt->status === 'submitted' ? $this->result($attempt) : $this->openAttempt($attempt)]);
    }

    /** Save an answer without revealing it; this endpoint is used for exams. */
    public function saveAnswer(Request $request, TheoryTestAttempt $attempt, TheoryAttemptAnswer $answer): JsonResponse
    {
        $this->ensureOwner($request, $attempt);
        $this->ensureAttemptAnswer($attempt, $answer);
        if ($attempt->mode === 'training') {
            throw ValidationException::withMessages(['attempt' => 'Utilisez la validation pédagogique pour cette série d’entraînement.']);
        }
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Cette série a déjà été validée et ne peut plus être modifiée.']);
        }
        if ($this->hasExpired($attempt)) {
            $this->finalizeAttempt($attempt);
            throw ValidationException::withMessages(['attempt' => 'Le temps est écoulé : votre examen a été remis automatiquement.']);
        }

        $selected = $this->validateSelection($request, $answer, false);
        $answer->update(['selected_option_ids' => $selected, 'answered_at' => now()]);

        return response()->json(['data' => ['id' => $answer->id, 'selected_option_ids' => $selected]]);
    }

    /** Training feedback is intentionally disclosed only after this answer is locked. */
    public function validateTrainingAnswer(Request $request, TheoryTestAttempt $attempt, TheoryAttemptAnswer $answer): JsonResponse
    {
        $student = $this->ensureOwner($request, $attempt);
        $this->ensureAttemptAnswer($attempt, $answer);
        if ($attempt->mode !== 'training' || $attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Cette réponse ne peut plus être validée.']);
        }
        if ($answer->is_correct !== null) {
            return response()->json(['data' => $this->trainingCorrection($answer)]);
        }

        $selected = $this->validateSelection($request, $answer, true);
        $snapshot = $answer->question_snapshot;
        $correct = collect($snapshot['correct_option_ids'] ?? [])->sort()->values()->all();
        $isCorrect = collect($selected)->sort()->values()->all() === $correct;
        DB::transaction(function () use ($answer, $selected, $isCorrect, $student, $snapshot) {
            $answer->update([
                'selected_option_ids' => $selected,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ]);
            $this->recordProgress($student->id, $snapshot['category_id'], $isCorrect);
        });

        return response()->json(['data' => $this->trainingCorrection($answer->fresh())]);
    }

    public function submit(Request $request, TheoryTestAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);
        if ($attempt->mode === 'training' && $attempt->answers()->whereNull('is_correct')->exists()) {
            throw ValidationException::withMessages(['attempt' => 'Validez chaque réponse avant de terminer la série d’entraînement.']);
        }

        return response()->json(['data' => $this->result($this->finalizeAttempt($attempt))]);
    }

    private function finalizeAttempt(TheoryTestAttempt $attempt): TheoryTestAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = TheoryTestAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'submitted') {
                return $locked;
            }

            $answers = $locked->answers()->lockForUpdate()->get();
            $correctTotal = 0;
            foreach ($answers as $answer) {
                $snapshot = $answer->question_snapshot;
                $selected = collect($answer->selected_option_ids ?? [])->sort()->values()->all();
                $correct = collect($snapshot['correct_option_ids'] ?? [])->sort()->values()->all();
                $isCorrect = $selected === $correct;
                $answer->update(['is_correct' => $isCorrect]);
                $correctTotal += $isCorrect ? 1 : 0;

                if ($locked->mode !== 'training') {
                    $this->recordProgress($locked->student_id, $snapshot['category_id'], $isCorrect);
                }
            }

            $locked->update([
                'status' => 'submitted',
                'correct_answers' => $correctTotal,
                'incorrect_answers' => max(0, $locked->total_questions - $correctTotal),
                'score' => $correctTotal,
                'passed' => $locked->mode === 'training' ? null : $correctTotal >= $locked->pass_threshold,
                'submitted_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    private function validateSelection(Request $request, TheoryAttemptAnswer $answer, bool $required): array
    {
        $rules = ['selected_option_ids' => [$required ? 'required' : 'nullable', 'array', $required ? 'min:1' : 'max:4'], 'selected_option_ids.*' => ['uuid']];
        if ($required) {
            $rules['selected_option_ids'][] = 'max:4';
        }
        $data = $request->validate($rules);
        $selected = array_values(array_unique($data['selected_option_ids'] ?? []));
        $snapshot = $answer->question_snapshot;
        $available = collect($snapshot['options'] ?? [])->pluck('id')->all();
        if (array_diff($selected, $available)) {
            throw ValidationException::withMessages(['selected_option_ids' => 'Une réponse sélectionnée ne correspond pas à cette question.']);
        }
        if (($snapshot['answer_type'] ?? 'single') === 'single' && count($selected) > 1) {
            throw ValidationException::withMessages(['selected_option_ids' => 'Cette question n’accepte qu’une seule réponse.']);
        }

        return $selected;
    }

    private function recordProgress(string $studentId, string $categoryId, bool $isCorrect): void
    {
        $progress = TheoryStudentProgress::firstOrNew(['student_id' => $studentId, 'category_id' => $categoryId]);
        $progress->questions_answered = (int) $progress->questions_answered + 1;
        $progress->correct_answers = (int) $progress->correct_answers + ($isCorrect ? 1 : 0);
        $progress->last_answered_at = now();
        $progress->save();
    }

    private function student(Request $request)
    {
        $student = $request->user()?->student;
        if (! $student) {
            abort(403, 'Cet espace est réservé aux élèves.');
        }

        return $student;
    }

    private function studentWithAccess(Request $request)
    {
        $student = $this->student($request);
        abort_unless($this->codeAccess->hasAccess($student), 403, 'Votre accès au Code de la Route nécessite un accès Code actif.');

        return $student;
    }

    private function ensureOwner(Request $request, TheoryTestAttempt $attempt)
    {
        $student = $this->studentWithAccess($request);
        abort_unless($attempt->student_id === $student->id, 403);

        return $student;
    }

    private function ensureAttemptAnswer(TheoryTestAttempt $attempt, TheoryAttemptAnswer $answer): void
    {
        abort_unless($answer->attempt_id === $attempt->id, 404);
    }

    private function hasExpired(TheoryTestAttempt $attempt): bool
    {
        return $attempt->mode === 'exam' && $attempt->expires_at?->isPast();
    }

    private function snapshot(TheoryQuestion $question): array
    {
        return [
            'question_id' => $question->id,
            'public_id' => $question->public_id,
            'category_id' => $question->category_id,
            'question_text' => $question->question_text,
            'difficulty' => $question->difficulty,
            'answer_type' => $question->answer_type,
            'media_type' => $question->media_type,
            'media_path' => $question->media_path,
            'explanation' => $question->explanation,
            'official_reference' => $question->official_reference,
            'options' => $question->options->map(fn ($option) => ['id' => $option->id, 'label' => $option->label, 'sort_order' => $option->sort_order])->values()->all(),
            'correct_option_ids' => $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all(),
        ];
    }

    private function openAttempt(TheoryTestAttempt $attempt): array
    {
        $attempt->loadMissing(['answers', 'series:id,title,type']);

        return [
            'id' => $attempt->id,
            'mode' => $attempt->mode,
            'status' => $attempt->status,
            'series' => $attempt->series ? ['id' => $attempt->series->id, 'title' => $attempt->series->title, 'type' => $attempt->series->type] : null,
            'total_questions' => $attempt->total_questions,
            'pass_threshold' => $attempt->pass_threshold,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'questions' => $attempt->answers->sortBy('sort_order')->values()->map(function (TheoryAttemptAnswer $answer) use ($attempt) {
                $snapshot = $answer->question_snapshot;

                return [
                    'answer_id' => $answer->id,
                    'selected_option_ids' => $answer->selected_option_ids ?? [],
                    'training_correction' => $attempt->mode === 'training' && $answer->is_correct !== null ? $this->trainingCorrection($answer) : null,
                    'question' => Arr::except($snapshot, ['correct_option_ids', 'explanation', 'official_reference']),
                ];
            })->all(),
        ];
    }

    private function trainingCorrection(TheoryAttemptAnswer $answer): array
    {
        $snapshot = $answer->question_snapshot;

        return [
            'is_correct' => (bool) $answer->is_correct,
            'correct_option_ids' => $snapshot['correct_option_ids'] ?? [],
            'explanation' => $snapshot['explanation'] ?? '',
            'official_reference' => $snapshot['official_reference'] ?? null,
        ];
    }

    private function result(TheoryTestAttempt $attempt): array
    {
        $attempt->loadMissing(['answers', 'series:id,title,type']);

        return [
            ...$this->attemptSummary($attempt),
            'mode' => $attempt->mode,
            'series' => $attempt->series ? ['id' => $attempt->series->id, 'title' => $attempt->series->title, 'type' => $attempt->series->type] : null,
            'pass_threshold' => $attempt->pass_threshold,
            'questions' => $attempt->answers->sortBy('sort_order')->values()->map(function (TheoryAttemptAnswer $answer) {
                $snapshot = $answer->question_snapshot;

                return [
                    'answer_id' => $answer->id,
                    'is_correct' => (bool) $answer->is_correct,
                    'selected_option_ids' => $answer->selected_option_ids ?? [],
                    'correct_option_ids' => $snapshot['correct_option_ids'] ?? [],
                    'question' => Arr::except($snapshot, ['correct_option_ids']),
                ];
            })->all(),
        ];
    }

    private function attemptSummary(TheoryTestAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'mode' => $attempt->mode,
            'series_id' => $attempt->series_id,
            'series_title' => $attempt->series?->title,
            'total_questions' => $attempt->total_questions,
            'correct_answers' => $attempt->correct_answers,
            'incorrect_answers' => $attempt->incorrect_answers,
            'score' => $attempt->score,
            'passed' => $attempt->passed,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
        ];
    }
}
