<?php

namespace App\Console\Commands;

use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Models\Theory\TheoryQuestion;
use App\Services\Theory\TheoryQuestionDeduplicator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportIllustratedTheoryManifest extends Command
{
    protected $signature = 'theory:import-illustrated-manifest
        {file : JSON manifest produced from an illustrated question source}
        {--batch=pdf-illustrated-original-80 : Stable identifier used for resumable imports}
        {--activate-all : Publish and activate every non-archived theory question after import}';

    protected $description = 'Imports illustrated Code de la Route questions and links their original scenario images.';

    /** @var array<string, string> */
    private const CATEGORY_CODES = [
        'Arrêt et stationnement' => 'circulation-routiere',
        'Autoroute' => 'route',
        'Dépassement' => 'circulation-routiere',
        'Feux et signalisation lumineuse' => 'circulation-routiere',
        'Giratoires' => 'circulation-routiere',
        'Piétons et cyclistes' => 'autres-usagers',
        'Priorités et intersections' => 'circulation-routiere',
        'Signalisation et conditions' => 'route',
    ];

    private const CODE_REFERENCE = 'https://www.legifrance.gouv.fr/codes/texte_lc/LEGITEXT000006074228/';

    public function handle(TheoryQuestionDeduplicator $deduplicator): int
    {
        $path = realpath((string) $this->argument('file'));
        if ($path === false || ! is_readable($path)) {
            $this->error('Illustrated-question manifest was not found or cannot be read.');

            return self::FAILURE;
        }

        try {
            $records = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->error('The illustrated-question manifest is not valid JSON.');

            return self::FAILURE;
        }

        if (! is_array($records) || $records === []) {
            $this->error('The illustrated-question manifest contains no records.');

            return self::FAILURE;
        }

        $categoryIds = TheoryCategory::query()
            ->whereIn('code', array_values(self::CATEGORY_CODES))
            ->pluck('id', 'code')
            ->all();
        $missingCategories = array_values(array_diff(array_values(self::CATEGORY_CODES), array_keys($categoryIds)));
        if ($missingCategories !== []) {
            $this->error('Theory categories are not ready. Missing: '.implode(', ', $missingCategories));

            return self::FAILURE;
        }

        $batchCode = trim((string) $this->option('batch'));
        $batch = TheoryImportBatch::query()->firstOrCreate(
            ['batch_code' => $batchCode],
            [
                'source' => 'User-provided illustrated Code de la Route PDF',
                'target_total' => 5030,
                'source_count' => count($records),
                'status' => 'pending',
                'metadata' => [],
            ],
        );

        $metadata = $batch->metadata ?? [];
        $processed = array_map('strval', $metadata['processed_source_ids'] ?? []);
        $existingPublicIds = DB::table('theory_questions')
            ->whereIn('public_id', array_map(
                static fn (array $record): string => 'CDR-PDF-'.str_pad((string) ((int) ($record['source_id'] ?? 0)), 4, '0', STR_PAD_LEFT),
                $records,
            ))
            ->pluck('public_id')
            ->flip()
            ->all();
        $toImport = [];
        $validationErrors = [];

        foreach ($records as $record) {
            $sourceId = (string) ($record['source_id'] ?? '');
            $publicId = 'CDR-PDF-'.str_pad((string) ((int) $sourceId), 4, '0', STR_PAD_LEFT);
            if (in_array($sourceId, $processed, true) && isset($existingPublicIds[$publicId])) {
                continue;
            }

            $prepared = $this->prepareRecord($record, $categoryIds, $deduplicator);
            if ($prepared === null) {
                $validationErrors[] = $sourceId;
                $processed[] = $sourceId;
                continue;
            }

            $toImport[] = $prepared;
        }

        $inserted = 0;
        $duplicates = 0;
        foreach (array_chunk($toImport, 100) as $chunk) {
            [$insertedNow, $duplicatesNow] = $this->flush($chunk);
            $inserted += $insertedNow;
            $duplicates += $duplicatesNow;
            foreach ($chunk as $record) {
                $processed[] = $record['source_id'];
            }
        }

        $processed = array_values(array_unique($processed));
        $complete = count($processed) >= count($records);
        $metadata['processed_source_ids'] = $processed;
        $metadata['remaining_in_source_batch'] = max(0, count($records) - count($processed));
        $metadata['invalid_source_ids'] = array_values(array_unique(array_merge($metadata['invalid_source_ids'] ?? [], $validationErrors)));

        $batch->update([
            'source_count' => count($records),
            'inserted_count' => (int) $batch->inserted_count + $inserted,
            'duplicate_count' => (int) $batch->duplicate_count + $duplicates,
            'metadata' => $metadata,
            'status' => $complete ? 'completed' : 'running',
            'started_at' => $batch->started_at ?? now(),
            'completed_at' => $complete ? now() : null,
        ]);

        $activated = 0;
        if ((bool) $this->option('activate-all')) {
            $activated = TheoryQuestion::query()->update([
                'status' => 'published',
                'is_active' => true,
                'requires_review' => false,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->table(
            ['batch_code', 'inserted_now', 'duplicates_now', 'invalid_now', 'processed', 'remaining', 'activated'],
            [[
                $batchCode,
                $inserted,
                $duplicates,
                count($validationErrors),
                count($processed),
                $metadata['remaining_in_source_batch'],
                $activated,
            ]],
        );

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $source
     * @param array<string, string> $categoryIds
     * @return array<string, mixed>|null
     */
    private function prepareRecord(array $source, array $categoryIds, TheoryQuestionDeduplicator $deduplicator): ?array
    {
        $sourceId = (int) ($source['source_id'] ?? 0);
        $categoryCode = self::CATEGORY_CODES[trim((string) ($source['theme'] ?? ''))] ?? null;
        $question = trim((string) ($source['question'] ?? ''));
        $explanation = trim((string) ($source['explanation'] ?? ''));
        $correctLetters = array_values(array_unique(array_filter(array_map(
            static fn ($letter): string => trim((string) $letter),
            (array) ($source['correct_letters'] ?? []),
        ))));
        $mediaPath = ltrim((string) ($source['media_path'] ?? ''), '/');

        if ($sourceId <= 0 || $categoryCode === null || ! isset($categoryIds[$categoryCode]) || $question === '' || $explanation === ''
            || $correctLetters === [] || ! str_starts_with($mediaPath, 'theory-diagrams/pdf-illustrated/')
            || ! is_file(public_path($mediaPath))) {
            return null;
        }

        $sourceOptions = (array) ($source['options'] ?? []);
        $options = [];
        foreach (['A', 'B', 'C', 'D'] as $index => $letter) {
            $label = trim((string) ($sourceOptions[$letter] ?? ''));
            if ($label === '') {
                return null;
            }
            $options[] = [
                'id' => (string) Str::uuid(),
                'label' => $label,
                'is_correct' => in_array($letter, $correctLetters, true),
                'sort_order' => $index + 1,
            ];
        }

        if (array_diff($correctLetters, ['A', 'B', 'C', 'D']) !== []
            || count(array_filter($options, static fn (array $option): bool => $option['is_correct'])) !== count($correctLetters)) {
            return null;
        }

        return [
            'source_id' => (string) $sourceId,
            'id' => (string) Str::uuid(),
            'public_id' => 'CDR-PDF-'.str_pad((string) $sourceId, 4, '0', STR_PAD_LEFT),
            'category_id' => $categoryIds[$categoryCode],
            'question_text' => $question,
            'difficulty' => 'medium',
            'answer_type' => count($correctLetters) > 1 ? 'multiple' : 'single',
            'media_type' => 'image',
            'media_path' => $mediaPath,
            'explanation' => $explanation,
            'official_reference' => self::CODE_REFERENCE,
            // A repeated prompt can describe distinct illustrated scenarios.
            // The image path is therefore part of the identity for this source.
            'content_hash' => hash('sha256', $deduplicator->hash($question).'|'.$mediaPath),
            'options' => $options,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{0: int, 1: int}
     */
    private function flush(array $records): array
    {
        $existing = DB::table('theory_questions')
            ->whereIn('content_hash', array_column($records, 'content_hash'))
            ->orWhereIn('public_id', array_column($records, 'public_id'))
            ->get(['content_hash', 'public_id']);
        $existingHashes = $existing->pluck('content_hash')->flip()->all();
        $existingPublicIds = $existing->pluck('public_id')->flip()->all();
        $seenHashes = [];
        $questionRows = [];
        $optionRows = [];
        $duplicates = 0;
        $now = now();

        foreach ($records as $record) {
            if (isset($existingHashes[$record['content_hash']]) || isset($existingPublicIds[$record['public_id']]) || isset($seenHashes[$record['content_hash']])) {
                $duplicates++;
                continue;
            }

            $seenHashes[$record['content_hash']] = true;
            $questionRows[] = [
                'id' => $record['id'],
                'public_id' => $record['public_id'],
                'category_id' => $record['category_id'],
                'question_text' => $record['question_text'],
                'difficulty' => $record['difficulty'],
                'answer_type' => $record['answer_type'],
                'media_type' => $record['media_type'],
                'media_path' => $record['media_path'],
                'explanation' => $record['explanation'],
                'official_reference' => $record['official_reference'],
                'status' => 'published',
                'is_active' => true,
                'requires_review' => false,
                'reviewed_at' => $now,
                'content_hash' => $record['content_hash'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($record['options'] as $option) {
                $optionRows[] = [
                    'id' => $option['id'],
                    'question_id' => $record['id'],
                    'label' => $option['label'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $option['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($questionRows !== []) {
            DB::transaction(function () use ($questionRows, $optionRows): void {
                DB::table('theory_questions')->insert($questionRows);
                DB::table('theory_answer_options')->insert($optionRows);
            });
        }

        return [count($questionRows), $duplicates];
    }
}
