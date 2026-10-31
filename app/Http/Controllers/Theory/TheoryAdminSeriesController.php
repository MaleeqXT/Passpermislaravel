<?php

namespace App\Http\Controllers\Theory;

use App\Http\Controllers\Controller;
use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheorySeries;
use App\Models\Theory\TheoryContentBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TheoryAdminSeriesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(['training', 'exam'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $series = TheorySeries::query()
            ->with('contentBank:id,code,name,status,is_active')
            ->withCount('seriesQuestions')
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate((int) ($data['per_page'] ?? 50));

        return response()->json($series->through(fn (TheorySeries $item) => $this->summary($item)));
    }

    public function show(TheorySeries $series): JsonResponse
    {
        $series->load([
            'seriesQuestions.question.category:id,code,name',
            'seriesQuestions.question.options:id,question_id,label,is_correct,sort_order',
            'seriesQuestions.question.parts.options',
        ])->loadCount('seriesQuestions');

        return response()->json(['data' => [
            ...$this->summary($series),
            'content_bank' => $series->contentBank?->only(['id', 'code', 'name', 'status', 'is_active']),
            'questions' => $series->seriesQuestions->map(fn ($assignment) => [
                'id' => $assignment->question->id,
                'public_id' => $assignment->question->public_id,
                'question_text' => $assignment->question->question_text,
                'difficulty' => $assignment->question->difficulty,
                'answer_type' => $assignment->question->answer_type,
                'media_type' => $assignment->question->media_type,
                'media_path' => $assignment->question->media_path,
                'image_path' => $assignment->question->image_path,
                'video_path' => $assignment->question->video_path,
                'audio_path' => $assignment->question->audio_path,
                'explanation' => $assignment->question->explanation,
                'options' => $assignment->question->options->map(fn ($option) => $option->only(['id', 'label', 'is_correct', 'sort_order']))->values(),
                'parts' => $assignment->question->parts->map(fn ($part) => ['id' => $part->id, 'question_text' => $part->question_text, 'answer_type' => $part->answer_type, 'explanation' => $part->explanation, 'correct_option_positions' => $part->correct_option_positions, 'sort_order' => $part->sort_order, 'options' => $part->options->map(fn ($option) => $option->only(['id', 'label', 'is_correct', 'sort_order']))->values()])->values(),
                'category' => $assignment->question->category?->only(['id', 'code', 'name']),
                'sort_order' => $assignment->sort_order,
            ])->values(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedSeries($request);
        $series = TheorySeries::create([
            ...$data,
            'content_bank_id' => $data['content_bank_id'] ?? $this->legacyBankId(),
            'status' => 'draft',
            'duration_minutes' => $data['type'] === 'exam' ? ($data['duration_minutes'] ?? 40) : null,
        ]);

        return response()->json(['message' => 'Série créée.', 'data' => $this->summary($series)], 201);
    }

    public function update(Request $request, TheorySeries $series): JsonResponse
    {
        $data = $this->validatedSeries($request);
        if (isset($data['content_bank_id']) && $data['content_bank_id'] !== $series->content_bank_id) {
            throw ValidationException::withMessages(['content_bank_id' => 'La banque d’une série ne peut pas être changée après création. Créez une nouvelle série dans la banque voulue.']);
        }
        unset($data['content_bank_id']);
        $series->update([
            ...$data,
            'duration_minutes' => $data['type'] === 'exam' ? ($data['duration_minutes'] ?? $series->duration_minutes ?? 40) : null,
        ]);

        return response()->json(['message' => 'Série mise à jour.', 'data' => $this->summary($series->fresh()->loadCount('seriesQuestions'))]);
    }

    public function replaceQuestions(Request $request, TheorySeries $series): JsonResponse
    {
        $data = $request->validate([
            'question_ids' => ['required', 'array', 'max:200'],
            'question_ids.*' => ['required', 'uuid', 'distinct'],
        ]);
        $questionIds = array_values($data['question_ids']);
        if ($series->status === 'published' && count($questionIds) < 40) {
            throw ValidationException::withMessages(['question_ids' => 'Une série publiée doit conserver au moins 40 questions dans son pool. Dépubliez-la avant de descendre sous ce seuil.']);
        }

        $questions = TheoryQuestion::query()
            ->whereIn('id', $questionIds)
            ->where('status', 'published')
            ->where('is_active', true)
            ->where('content_bank_id', $series->content_bank_id)
            ->count();
        if ($questions !== count($questionIds)) {
            throw ValidationException::withMessages(['question_ids' => 'Les questions doivent être publiées, actives et appartenir à la même banque que la série.']);
        }

        DB::transaction(function () use ($series, $questionIds) {
            $series->seriesQuestions()->delete();
            foreach ($questionIds as $index => $questionId) {
                $series->seriesQuestions()->create(['question_id' => $questionId, 'sort_order' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Questions de la série enregistrées.', 'data' => $this->summary($series->fresh()->loadCount('seriesQuestions'))]);
    }

    public function updatePublication(Request $request, TheorySeries $series): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
        if ($data['status'] === 'published') {
            $this->ensureCanPublish($series);
        }
        $series->update(['status' => $data['status']]);

        return response()->json(['message' => 'Statut de la série mis à jour.', 'data' => $this->summary($series->fresh()->loadCount('seriesQuestions'))]);
    }

    public function questionBank(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'content_bank_id' => ['nullable', 'uuid', 'exists:theory_content_banks,id'],
        ]);

        $questions = TheoryQuestion::query()
            ->with('category:id,code,name')
            ->when($data['content_bank_id'] ?? null, fn ($query, $bankId) => $query->where('content_bank_id', $bankId))
            ->where('status', 'published')
            ->where('is_active', true)
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($data['search'] ?? null, function ($query, string $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('public_id', 'like', "%{$search}%")
                        ->orWhere('question_text', 'like', "%{$search}%");
                });
            })
            ->orderBy('public_id')
            ->paginate((int) ($data['per_page'] ?? 20));

        return response()->json($questions->through(fn (TheoryQuestion $question) => [
            'id' => $question->id,
            'public_id' => $question->public_id,
            'question_text' => $question->question_text,
            'category' => $question->category?->only(['id', 'code', 'name']),
        ]));
    }

    private function validatedSeries(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['training', 'exam'])],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:4000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'content_bank_id' => ['nullable', 'uuid', 'exists:theory_content_banks,id'],
        ]);
    }

    private function ensureCanPublish(TheorySeries $series): void
    {
        $assignments = $series->seriesQuestions()
            ->whereHas('question', fn ($query) => $query->where('status', 'published')->where('is_active', true))
            ->count();
        if ($assignments < 40 || $series->seriesQuestions()->count() < 40) {
            throw ValidationException::withMessages(['status' => 'Une série ne peut être publiée que lorsqu’elle contient au moins 40 questions publiées et actives. Chaque élève recevra 40 questions tirées aléatoirement de ce pool.']);
        }
    }

    private function summary(TheorySeries $series): array
    {
        return [
            'id' => $series->id,
            'content_bank_id' => $series->content_bank_id,
            'content_bank' => $series->contentBank?->only(['id', 'code', 'name', 'status', 'is_active']),
            'type' => $series->type,
            'title' => $series->title,
            'description' => $series->description,
            'status' => $series->status,
            'duration_minutes' => $series->duration_minutes,
            'sort_order' => $series->sort_order,
            'questions_count' => (int) ($series->series_questions_count ?? $series->seriesQuestions()->count()),
        ];
    }

    private function legacyBankId(): string
    {
        return TheoryContentBank::query()->where('code', 'legacy')->value('id')
            ?? throw ValidationException::withMessages(['content_bank_id' => 'Banque legacy introuvable.']);
    }
}
