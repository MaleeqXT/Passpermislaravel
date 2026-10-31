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
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class TheoryAdminImportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => TheoryImportBatch::query()->with('contentBank:id,code,name')->latest()->limit(30)->get()]);
    }

    /** Import is deliberately admin-only and never publishes imported records automatically. */
    public function store(Request $request, TheoryQuestionDeduplicator $deduplicator): JsonResponse
    {
        $data = $request->validate([
            'dataset' => ['required', 'file', 'mimes:csv,txt,json,xlsx', 'max:102400'],
            'media_archive' => ['nullable', 'file', 'mimes:zip', 'max:512000'],
            'source' => ['required', 'string', 'max:100'],
            'content_bank_code' => ['required', 'string', 'max:96', 'exists:theory_content_banks,code'],
            'update_existing' => ['nullable', 'boolean'],
        ]);
        $source = trim($data['source']);
        $bank = TheoryContentBank::query()->where('code', $data['content_bank_code'])->firstOrFail();
        $batch = TheoryImportBatch::create([
            'batch_code' => 'admin-'.now()->format('YmdHis').'-'.Str::lower(Str::random(7)),
            'source' => $source,
            'content_bank_id' => $bank->id,
            'status' => 'running',
            'started_at' => now(),
            'metadata' => ['errors' => [], 'update_existing' => (bool) ($data['update_existing'] ?? false)],
        ]);
        $rows = $this->rows($request->file('dataset')->getRealPath(), $request->file('dataset')->getClientOriginalExtension());
        $media = $request->hasFile('media_archive') ? $this->mediaIndex($request->file('media_archive')->getRealPath()) : [];
        $inserted = 0; $updated = 0; $duplicates = 0; $errors = [];
        foreach ($rows as $offset => $row) {
            try {
                $result = DB::transaction(fn () => $this->persist($this->normalize($row), $source, $bank, (bool) ($data['update_existing'] ?? false), $deduplicator, $media));
                $inserted += $result === 'inserted'; $updated += $result === 'updated'; $duplicates += $result === 'duplicate';
            } catch (\Throwable $exception) {
                $errors[] = ['row' => $offset + 2, 'message' => Str::limit($exception->getMessage(), 500)];
            }
            if (($offset + 1) % 100 === 0) $batch->update(['source_count' => count($rows), 'inserted_count' => $inserted, 'duplicate_count' => $duplicates, 'review_count' => $inserted + $updated, 'metadata' => ['errors' => array_slice($errors, 0, 200), 'updated_count' => $updated]]);
        }
        $batch->update(['source_count' => count($rows), 'inserted_count' => $inserted, 'duplicate_count' => $duplicates, 'review_count' => $inserted + $updated, 'status' => $errors === [] ? 'completed' : 'completed_with_errors', 'completed_at' => now(), 'metadata' => ['errors' => array_slice($errors, 0, 200), 'updated_count' => $updated, 'media_archive_supplied' => $media !== []]]);
        return response()->json(['data' => $batch->fresh(), 'message' => 'Import terminé : aucun enregistrement n’a été publié automatiquement.'], 201);
    }

    private function rows(string $path, string $extension): array
    {
        if (strtolower($extension) === 'json') { $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); return array_is_list($rows) ? $rows : ($rows['questions'] ?? []); }
        if (strtolower($extension) === 'xlsx') { $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true); $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows) ?: []); return array_map(fn ($row) => array_combine($headers, array_values($row)), $rows); }
        $handle = fopen($path, 'rb'); $headers = array_map(fn ($value) => strtolower(trim((string) $value)), fgetcsv($handle) ?: []); $rows = []; while (($row = fgetcsv($handle)) !== false) if (count($row) === count($headers)) $rows[] = array_combine($headers, $row); fclose($handle); return $rows;
    }

    private function normalize(array $row): array
    {
        $get = fn (...$keys) => trim((string) collect($keys)->map(fn ($key) => $row[$key] ?? null)->first(fn ($value) => $value !== null && $value !== ''));
        $answers = array_values(array_filter(array_map('trim', preg_split('/[,;|]/', strtoupper($get('answer', 'answers', 'correct_answers')) ?: ''))));
        $options = []; foreach (['a', 'b', 'c', 'd'] as $i => $letter) if (($label = $get($letter, 'option_'.($i + 1))) !== '') $options[] = ['label' => $label, 'is_correct' => in_array(strtoupper($letter), $answers, true)];
        if (count($options) < 2 || $answers === []) throw new \RuntimeException('Réponses A–D et réponse(s) correcte(s) obligatoires.');
        return ['external_id' => $get('external_id', 'id', 'question_id'), 'public_id' => $get('public_id'), 'category' => $get('category', 'theme'), 'question_text' => $get('question', 'question_text', 'text'), 'explanation' => $get('explanation'), 'difficulty' => $get('difficulty') ?: 'medium', 'options' => $options, 'image' => $get('image', 'image_file'), 'video' => $get('video', 'video_file'), 'audio' => $get('audio', 'audio_file')];
    }

    private function persist(array $record, string $source, TheoryContentBank $bank, bool $update, TheoryQuestionDeduplicator $deduplicator, array $media): string
    {
        if ($record['external_id'] === '' || $record['question_text'] === '' || $record['explanation'] === '') throw new \RuntimeException('Identifiant stable, question et explication obligatoires.');
        $category = TheoryCategory::firstOrCreate(['code' => Str::slug($record['category'] ?: 'non-classe')], ['name' => $record['category'] ?: 'Non classée', 'is_active' => true]);
        $existing = TheoryQuestion::withTrashed()->where('import_source', $source)->where('external_id', $record['external_id'])->first() ?: ($record['public_id'] ? TheoryQuestion::withTrashed()->where('public_id', $record['public_id'])->first() : null);
        $hash = $deduplicator->hash($record['question_text']);
        if (! $existing && TheoryQuestion::withTrashed()->where('content_hash', $hash)->exists()) return 'duplicate';
        if ($existing && ! $update) return 'duplicate';
        if ($existing && $existing->content_bank_id !== $bank->id) throw new \RuntimeException('Cet identifiant d’import appartient déjà à une autre banque de contenu.');
        $attributes = ['content_bank_id' => $bank->id, 'category_id' => $category->id, 'question_text' => $record['question_text'], 'difficulty' => in_array($record['difficulty'], ['easy', 'medium', 'hard'], true) ? $record['difficulty'] : 'medium', 'answer_type' => count(array_filter($record['options'], fn ($option) => $option['is_correct'])) > 1 ? 'multiple' : 'single', 'explanation' => $record['explanation'], 'content_hash' => $hash, 'import_source' => $source, 'external_id' => $record['external_id'], 'content_origin' => 'admin-import', 'license_reference' => $source, 'review_status' => 'pending', 'status' => 'draft', 'is_active' => false, 'requires_review' => true, 'review_notes' => 'Import administrateur : validation éditoriale et de licence requise.'];
        $question = $existing ?: new TheoryQuestion(['public_id' => $record['public_id'] ?: 'IMP-'.Str::upper(Str::random(10))]); $question->fill($attributes)->save(); $question->options()->delete(); $question->options()->createMany(collect($record['options'])->values()->map(fn ($option, $index) => $option + ['sort_order' => $index + 1])->all());
        foreach (['image' => ['jpg','jpeg','png','webp'], 'video' => ['mp4'], 'audio' => ['mp3']] as $kind => $extensions) if ($record[$kind] !== '' && isset($media[basename($record[$kind])])) { $file = $media[basename($record[$kind])]; if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) $question->update(["{$kind}_path" => Storage::disk('public')->putFile("theory/questions/{$question->id}/{$kind}", new \Illuminate\Http\File($file))]); }
        return $existing ? 'updated' : 'inserted';
    }

    private function mediaIndex(string $path): array
    {
        $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new \RuntimeException('Archive média ZIP illisible.'); $root = storage_path('app/theory-import-media/'.Str::uuid()); mkdir($root, 0755, true); $index = [];
        for ($i = 0; $i < $zip->numFiles; $i++) { $name = $zip->getNameIndex($i); if (! $name || str_contains($name, '..') || str_ends_with($name, '/')) continue; $target = $root.'/'.basename($name); copy("zip://{$path}#{$name}", $target); $index[basename($name)] = $target; } $zip->close(); return $index;
    }
}
