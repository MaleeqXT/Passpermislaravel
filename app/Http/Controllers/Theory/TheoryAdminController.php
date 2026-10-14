<?php

namespace App\Http\Controllers\Theory;

use App\Http\Controllers\Controller;
use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Models\Theory\TheoryQuestion;
use App\Services\Theory\TheoryQuestionDeduplicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TheoryAdminController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = TheoryCategory::query()
            ->withCount([
                'questions as total_questions_count',
                'questions as published_questions_count' => fn ($query) => $query->where('status', 'published')->where('is_active', true),
                'questions as review_questions_count' => fn ($query) => $query->where('requires_review', true),
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'category_id' => ['nullable', 'uuid', 'exists:theory_categories,id'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $questions = TheoryQuestion::query()
            ->with(['category:id,code,name', 'options:id,question_id,label,is_correct,sort_order'])
            ->withCount('attemptAnswers')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('question_text', 'like', "%{$search}%")
                        ->orWhere('public_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 25);

        return response()->json($questions);
    }

    public function statistics(): JsonResponse
    {
        return response()->json(['data' => [
            'total_questions' => TheoryQuestion::count(),
            'published_questions' => TheoryQuestion::where('status', 'published')->where('is_active', true)->count(),
            'draft_questions' => TheoryQuestion::where('status', 'draft')->count(),
            'review_questions' => TheoryQuestion::where('requires_review', true)->count(),
            'target_questions' => 4950,
            'batches' => TheoryImportBatch::query()->latest()->limit(10)->get(),
        ]]);
    }

    public function store(Request $request, TheoryQuestionDeduplicator $deduplicator): JsonResponse
    {
        $data = $this->validatedQuestion($request);
        $this->rejectExactDuplicate($deduplicator, $data['question_text']);

        $question = DB::transaction(function () use ($data, $deduplicator) {
            $question = TheoryQuestion::create($this->questionAttributes($data, $deduplicator));
            $this->replaceOptions($question, $data['options'], $data['answer_type']);
            return $question;
        });

        return response()->json(['message' => 'Question créée.', 'data' => $this->loadQuestion($question)], 201);
    }

    public function update(Request $request, TheoryQuestion $question, TheoryQuestionDeduplicator $deduplicator): JsonResponse
    {
        $data = $this->validatedQuestion($request);
        $this->rejectExactDuplicate($deduplicator, $data['question_text'], $question->id);

        DB::transaction(function () use ($question, $data, $deduplicator) {
            $question->update($this->questionAttributes($data, $deduplicator, $question));
            $this->replaceOptions($question, $data['options'], $data['answer_type']);
        });

        return response()->json(['message' => 'Question mise à jour.', 'data' => $this->loadQuestion($question)]);
    }

    public function updatePublication(Request $request, TheoryQuestion $question): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_active' => ['nullable', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['status'] === 'published' && $question->requires_review) {
            throw ValidationException::withMessages(['status' => 'Cette question doit être revue avant publication.']);
        }

        $question->update([
            'status' => $data['status'],
            'is_active' => $data['is_active'] ?? $question->is_active,
            'review_notes' => $data['review_notes'] ?? $question->review_notes,
            'reviewed_at' => $data['status'] === 'published' ? now() : $question->reviewed_at,
            'reviewed_by' => $data['status'] === 'published' ? $request->user()->id : $question->reviewed_by,
        ]);

        return response()->json(['message' => 'Statut mis à jour.', 'data' => $this->loadQuestion($question)]);
    }

    public function uploadMedia(Request $request, TheoryQuestion $question): JsonResponse
    {
        $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg,mp4,webm', 'max:20480'],
        ]);

        $file = $request->file('media');
        $mediaType = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        $path = $file->store("theory/questions/{$question->id}", 'public');

        if ($question->media_path && str_starts_with($question->media_path, 'theory/questions/')) {
            Storage::disk('public')->delete($question->media_path);
        }

        $question->update(['media_path' => $path, 'media_type' => $mediaType]);

        return response()->json(['message' => 'Média ajouté.', 'data' => $this->loadQuestion($question)]);
    }

    public function destroy(TheoryQuestion $question): JsonResponse
    {
        $question->update(['status' => 'archived', 'is_active' => false]);
        $question->delete();

        return response()->json(['message' => 'Question archivée.']);
    }

    public function duplicates(Request $request, TheoryQuestionDeduplicator $deduplicator): JsonResponse
    {
        $data = $request->validate(['question_text' => ['required', 'string', 'min:12', 'max:5000'], 'ignore_id' => ['nullable', 'uuid']]);
        return response()->json(['data' => $deduplicator->nearDuplicates($data['question_text'], $data['ignore_id'] ?? null)]);
    }

    private function validatedQuestion(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'uuid', 'exists:theory_categories,id'],
            'question_text' => ['required', 'string', 'min:12', 'max:5000'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'answer_type' => ['required', Rule::in(['single', 'multiple'])],
            'explanation' => ['required', 'string', 'min:12', 'max:6000'],
            'official_reference' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'is_active' => ['nullable', 'boolean'],
            'requires_review' => ['nullable', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'options' => ['required', 'array', 'min:2', 'max:4'],
            'options.*.label' => ['required', 'string', 'min:1', 'max:1000'],
            'options.*.is_correct' => ['required', 'boolean'],
        ]);
    }

    private function questionAttributes(array $data, TheoryQuestionDeduplicator $deduplicator, ?TheoryQuestion $existing = null): array
    {
        if (($data['status'] ?? $existing?->status) === 'published' && ($data['requires_review'] ?? $existing?->requires_review)) {
            throw ValidationException::withMessages(['status' => 'Une question à relire ne peut pas être publiée.']);
        }

        return [
            'public_id' => $existing?->public_id ?? 'CDR-'.Str::upper(Str::random(10)),
            'category_id' => $data['category_id'],
            'question_text' => trim($data['question_text']),
            'difficulty' => $data['difficulty'],
            'answer_type' => $data['answer_type'],
            'explanation' => trim($data['explanation']),
            'official_reference' => $data['official_reference'] ?? null,
            'status' => $data['status'] ?? $existing?->status ?? 'draft',
            'is_active' => $data['is_active'] ?? $existing?->is_active ?? true,
            'requires_review' => $data['requires_review'] ?? false,
            'review_notes' => $data['review_notes'] ?? null,
            'content_hash' => $deduplicator->hash($data['question_text']),
        ];
    }

    private function replaceOptions(TheoryQuestion $question, array $options, string $answerType): void
    {
        $correct = collect($options)->filter(fn (array $option) => (bool) $option['is_correct'])->count();
        if (($answerType === 'single' && $correct !== 1) || ($answerType === 'multiple' && $correct < 2)) {
            throw ValidationException::withMessages(['options' => 'Indiquez une seule bonne réponse ou au moins deux bonnes réponses selon le type choisi.']);
        }

        $question->options()->delete();
        $question->options()->createMany(collect($options)->values()->map(fn (array $option, int $index) => [
            'label' => trim($option['label']),
            'is_correct' => (bool) $option['is_correct'],
            'sort_order' => $index + 1,
        ])->all());
    }

    private function rejectExactDuplicate(TheoryQuestionDeduplicator $deduplicator, string $text, ?string $ignoreId = null): void
    {
        $query = TheoryQuestion::query()->where('content_hash', $deduplicator->hash($text));
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['question_text' => 'Une question identique existe déjà dans la banque.']);
        }
    }

    private function loadQuestion(TheoryQuestion $question): TheoryQuestion
    {
        return $question->fresh()->load(['category:id,code,name', 'options:id,question_id,label,is_correct,sort_order']);
    }
}
