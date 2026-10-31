<?php

namespace App\Http\Controllers\Theory;

use App\Http\Controllers\Controller;
use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheoryContentBank;
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
        $bankId = $this->adminDefaultBankId();
        $categories = TheoryCategory::query()
            ->whereHas('questions', fn ($query) => $query->where('content_bank_id', $bankId))
            ->withCount([
                'questions as total_questions_count' => fn ($query) => $query->where('content_bank_id', $bankId),
                'questions as published_questions_count' => fn ($query) => $query->where('content_bank_id', $bankId)->where('status', 'published')->where('is_active', true),
                'questions as review_questions_count' => fn ($query) => $query->where('content_bank_id', $bankId)->where('requires_review', true),
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
            'content_bank_id' => ['nullable', 'uuid', 'exists:theory_content_banks,id'],
            'review_status' => ['nullable', Rule::in(['pending', 'approved', 'needs_correction', 'rejected'])],
            'requires_review' => ['nullable', 'boolean'],
        ]);

        // The ordinary administration screen is the editorial workspace for
        // original-fr-2026. A caller may still explicitly select another bank
        // later for historical lookup, but mixed-bank lists are never returned.
        $bankId = $filters['content_bank_id'] ?? $this->adminDefaultBankId();
        $questions = TheoryQuestion::query()
            ->with(['contentBank:id,code,name,status,is_active', 'category:id,code,name', 'options:id,question_id,label,is_correct,sort_order', 'parts.options'])
            ->withCount('attemptAnswers')
            ->where('content_bank_id', $bankId)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['review_status'] ?? null, function ($query, $reviewStatus) {
                $query->whereIn('review_status', $reviewStatus === 'pending' ? ['pending', 'pending_editorial_review'] : [$reviewStatus]);
            })
            ->when(array_key_exists('requires_review', $filters) && $filters['requires_review'] !== null, fn ($query) => $query->where('requires_review', $filters['requires_review']))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('question_text', 'like', "%{$search}%")
                        ->orWhere('public_id', 'like', "%{$search}%");
                });
            })
            // Keep the imported bank in its stable external-ID order. This
            // starts the admin table at PFF-R2026-0001, where the first
            // completed image records are available, instead of hiding them
            // behind the newest no-image drafts.
            ->orderBy('external_id')
            ->orderBy('public_id')
            ->paginate($filters['per_page'] ?? 25);

        // Keep the database path as the source of truth while returning a
        // browser-safe public URL. The UI must never have to infer a file
        // name or expose a local Windows filesystem path.
        $questions->through(fn (TheoryQuestion $question) => $this->questionPayload($question));

        return response()->json($questions);
    }

    public function statistics(): JsonResponse
    {
        $bankId = $this->adminDefaultBankId();
        $questions = TheoryQuestion::query()->where('content_bank_id', $bankId);

        return response()->json(['data' => [
            'total_questions' => (clone $questions)->count(),
            'published_questions' => (clone $questions)->where('status', 'published')->where('is_active', true)->count(),
            'draft_questions' => (clone $questions)->where('status', 'draft')->count(),
            'review_questions' => (clone $questions)->where('requires_review', true)->count(),
            'target_questions' => 4950,
            'batches' => TheoryImportBatch::query()->latest()->limit(10)->get(),
            // This endpoint feeds the current Admin Questions workspace, not
            // the historical-bank browser. Keep its auxiliary bank metadata
            // aligned with the same original-fr-2026 scope as its counters.
            'content_banks' => TheoryContentBank::query()->whereKey($bankId)->withCount('questions')->get(),
        ]]);
    }

    /**
     * Editorial work is intentionally isolated to the generated draft bank.
     * It exposes counters only; no action here can activate or publish content.
     */
    public function editorialReview(): JsonResponse
    {
        $bank = TheoryContentBank::query()->where('code', 'original-fr-2026')->firstOrFail();
        $questions = TheoryQuestion::query()->where('content_bank_id', $bank->id);

        return response()->json(['data' => [
            'bank' => $bank->only(['id', 'code', 'name', 'status', 'is_active']),
            'total' => (clone $questions)->count(),
            'pending' => (clone $questions)->whereIn('review_status', ['pending', 'pending_editorial_review'])->count(),
            'approved' => (clone $questions)->where('review_status', 'approved')->count(),
            'needs_correction' => (clone $questions)->where('review_status', 'needs_correction')->count(),
            'rejected' => (clone $questions)->where('review_status', 'rejected')->count(),
            'remaining' => (clone $questions)->where('status', 'draft')->where('requires_review', true)->count(),
        ]]);
    }

    public function store(Request $request, TheoryQuestionDeduplicator $deduplicator): JsonResponse
    {
        $data = $this->validatedQuestion($request);
        $this->rejectExactDuplicate($deduplicator, $data['question_text']);

        $question = DB::transaction(function () use ($data, $deduplicator) {
            $question = TheoryQuestion::create($this->questionAttributes($data, $deduplicator));
            $this->replaceOptions($question, $data['options'] ?? [], $data['answer_type'], $data['parts'] ?? []);
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
            $this->replaceOptions($question, $data['options'] ?? [], $data['answer_type'], $data['parts'] ?? []);
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

        if ($this->isOriginalDraftBank($question)) {
            throw ValidationException::withMessages(['status' => 'La banque original-fr-2026 reste en brouillon et inactive pendant la revue éditoriale.']);
        }

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

    public function review(Request $request, TheoryQuestion $question): JsonResponse
    {
        $data = $request->validate([
            'review_status' => ['required', Rule::in(['approved', 'needs_correction', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! $this->isOriginalDraftBank($question) || $question->status !== 'draft') {
            throw ValidationException::withMessages(['question' => 'Seules les questions brouillon de original-fr-2026 peuvent être revues ici.']);
        }

        $question->update([
            'review_status' => $data['review_status'],
            'requires_review' => $data['review_status'] !== 'approved',
            'review_notes' => $data['review_notes'] ?? $question->review_notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
            // Never transition this bank outside the protected draft state.
            'status' => 'draft',
            'is_active' => false,
        ]);

        return response()->json(['message' => 'Décision éditoriale enregistrée.', 'data' => $this->loadQuestion($question)]);
    }

    public function uploadMedia(Request $request, TheoryQuestion $question): JsonResponse
    {
        $data = $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mp3', 'max:51200'],
            'kind' => ['nullable', Rule::in(['image', 'video', 'audio'])],
        ]);

        $file = $request->file('media');
        $mime = (string) $file->getMimeType();
        $mediaType = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') ? 'audio' : 'image');
        if (isset($data['kind']) && $data['kind'] !== $mediaType) {
            throw ValidationException::withMessages(['media' => 'Le type de fichier ne correspond pas au média demandé.']);
        }
        $column = "{$mediaType}_path";
        $path = $file->store("theory/questions/{$question->id}/{$mediaType}", 'public');

        if ($question->{$column} && str_starts_with($question->{$column}, 'theory/questions/')) {
            Storage::disk('public')->delete($question->{$column});
        }

        // Keep the legacy primary-media fields populated for existing student playback.
        $question->update([
            $column => $path,
            'media_path' => $mediaType === 'audio' ? $question->media_path : $path,
            'media_type' => $mediaType === 'audio' ? $question->media_type : $mediaType,
        ]);

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
        $data = $request->validate([
            'category_id' => ['required', 'uuid', 'exists:theory_categories,id'],
            'content_bank_id' => ['nullable', 'uuid', 'exists:theory_content_banks,id'],
            'question_text' => ['required', 'string', 'min:3', 'max:5000'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'answer_type' => ['required', Rule::in(['single', 'multiple'])],
            'explanation' => ['nullable', 'string', 'max:6000'],
            'official_reference' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'is_active' => ['nullable', 'boolean'],
            'requires_review' => ['nullable', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'options' => ['nullable', 'array', 'min:2', 'max:4', 'required_without:parts'],
            'options.*.label' => ['required', 'string', 'min:1', 'max:1000'],
            'options.*.is_correct' => ['required', 'boolean'],
            'parts' => ['nullable', 'array', 'size:2'],
            'parts.*.question_text' => ['required', 'string', 'min:3', 'max:5000'],
            'parts.*.answer_type' => ['nullable', Rule::in(['single', 'multiple'])],
            'parts.*.explanation' => ['nullable', 'string', 'max:6000'],
            'parts.*.correct_option_positions' => ['required_with:parts', 'array', 'min:1', 'max:2'],
            'parts.*.correct_option_positions.*' => ['integer', 'between:1,4'],
        ]);

        if (! empty($data['parts'])) {
            if (count($data['options'] ?? []) !== 4) {
                throw ValidationException::withMessages(['options' => 'Les questions multiples nécessitent exactement quatre réponses partagées : A, B, C et D.']);
            }
            foreach (array_values($data['parts']) as $partIndex => $part) {
                $allowed = $partIndex === 0 ? [1, 2] : [3, 4];
                if (array_diff($part['correct_option_positions'] ?? [], $allowed)) {
                    throw ValidationException::withMessages(["parts.$partIndex.correct_option_positions" => 'La question 1 utilise A/B et la question 2 utilise C/D.']);
                }
            }
        }

        return $data;
    }

    private function questionAttributes(array $data, TheoryQuestionDeduplicator $deduplicator, ?TheoryQuestion $existing = null): array
    {
        if ($existing && array_key_exists('content_bank_id', $data) && $data['content_bank_id'] !== $existing->content_bank_id) {
            throw ValidationException::withMessages(['content_bank_id' => 'Une question existante ne peut pas être déplacée vers une autre banque de contenu.']);
        }

        if (($data['status'] ?? $existing?->status) === 'published' && ($data['requires_review'] ?? $existing?->requires_review)) {
            throw ValidationException::withMessages(['status' => 'Une question à relire ne peut pas être publiée.']);
        }

        $contentBankId = $data['content_bank_id'] ?? $existing?->content_bank_id ?? TheoryContentBank::query()->where('code', 'legacy')->value('id');
        $status = $data['status'] ?? $existing?->status ?? 'draft';
        $isActive = $data['is_active'] ?? $existing?->is_active ?? true;
        $contentBankCode = TheoryContentBank::query()->whereKey($contentBankId)->value('code');
        if ($contentBankCode === 'original-fr-2026' && ($status !== 'draft' || $isActive)) {
            throw ValidationException::withMessages(['status' => 'La banque original-fr-2026 doit rester en brouillon et inactive jusqu’à son activation explicite.']);
        }

        return [
            'public_id' => $existing?->public_id ?? 'CDR-'.Str::upper(Str::random(10)),
            'content_bank_id' => $contentBankId,
            'category_id' => $data['category_id'],
            'question_text' => trim($data['question_text']),
            'difficulty' => $data['difficulty'],
            'answer_type' => $data['answer_type'],
            'explanation' => trim((string) ($data['explanation'] ?? $data['parts'][0]['explanation'] ?? '')),
            'official_reference' => $data['official_reference'] ?? null,
            'status' => $status,
            'is_active' => $isActive,
            'requires_review' => $data['requires_review'] ?? false,
            'review_notes' => $data['review_notes'] ?? null,
            'content_hash' => $deduplicator->hash($data['question_text']),
        ];
    }

    private function replaceOptions(TheoryQuestion $question, array $options, string $answerType, array $parts = []): void
    {
        if ($parts !== []) {
            $question->options()->delete();
            $question->parts()->delete();
            $question->options()->createMany(collect($options)->values()->map(fn (array $option, int $index) => [
                'label' => trim($option['label']), 'is_correct' => false, 'sort_order' => $index + 1,
            ])->all());
            foreach (array_values($parts) as $partIndex => $part) {
                $question->parts()->create([
                    'question_text' => trim($part['question_text']),
                    'answer_type' => count($part['correct_option_positions'] ?? []) > 1 ? 'multiple' : 'single',
                    'explanation' => trim((string) ($part['explanation'] ?? '')) ?: null,
                    'correct_option_positions' => array_values($part['correct_option_positions']),
                    'sort_order' => $partIndex + 1,
                ]);
            }
            return;
        }
        $question->parts()->delete();
        $this->validateOptionSet($options, $answerType);
        $question->options()->delete();
        $question->options()->createMany(collect($options)->values()->map(fn (array $option, int $index) => [
            'label' => trim($option['label']), 'is_correct' => (bool) $option['is_correct'], 'sort_order' => $index + 1,
        ])->all());
    }

    private function validateOptionSet(array $options, string $answerType): void
    {
        $correct = collect($options)->filter(fn (array $option) => (bool) $option['is_correct'])->count();
        if (($answerType === 'single' && $correct !== 1) || ($answerType === 'multiple' && $correct < 2)) {
            throw ValidationException::withMessages(['options' => 'Indiquez une seule bonne réponse ou au moins deux bonnes réponses selon le type choisi.']);
        }

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
        return $this->appendMediaUrls($question->fresh()->load(['contentBank:id,code,name,status,is_active', 'category:id,code,name', 'options:id,question_id,label,is_correct,sort_order', 'parts.options']));
    }

    /**
     * The admin response deliberately keeps both the stored path and its
     * resolved URL. Consumers can use image_path to identify the registered
     * media record, and image_url to render it without knowing the storage
     * layout.
     */
    private function questionPayload(TheoryQuestion $question): array
    {
        return $this->appendMediaUrls($question)->toArray();
    }

    private function appendMediaUrls(TheoryQuestion $question): TheoryQuestion
    {
        $question->setAttribute('image_url', $this->publicMediaUrl($question->image_path));
        $question->setAttribute('video_url', $this->publicMediaUrl($question->video_path));
        $question->setAttribute('audio_url', $this->publicMediaUrl($question->audio_path));

        return $question;
    }

    private function publicMediaUrl(?string $path): ?string
    {
        if (! $path || preg_match('/^[A-Za-z]:/', $path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $url = Storage::disk('public')->url(ltrim(str_replace('\\', '/', $path), '/'));
        $parsed = parse_url($url);

        // APP_URL points to the WAMP root locally, whereas the React dev
        // server proxies /storage to Laravel's public document root. A
        // relative storage URL therefore works in both environments and
        // never exposes a local host/path chosen by the server.
        return isset($parsed['path'])
            ? $parsed['path'].(isset($parsed['query']) ? '?'.$parsed['query'] : '')
            : $url;
    }

    private function isOriginalDraftBank(TheoryQuestion $question): bool
    {
        return $question->contentBank()->where('code', 'original-fr-2026')->exists();
    }

    private function adminDefaultBankId(): string
    {
        return TheoryContentBank::query()
            ->where('code', 'original-fr-2026')
            ->value('id')
            ?? throw new \RuntimeException('La banque original-fr-2026 est introuvable.');
    }
}
