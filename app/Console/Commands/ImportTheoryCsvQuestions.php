<?php

namespace App\Console\Commands;

use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Services\Theory\TheoryQuestionDeduplicator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportTheoryCsvQuestions extends Command
{
    protected $signature = 'theory:import-csv
        {file : Absolute path of the CSV file to import}
        {--batch=csv-original-practice-4950 : Stable identifier used for resumable imports}
        {--limit=0 : Maximum source rows to process (0 imports all remaining rows)}';

    protected $description = 'Imports a validated Code de la Route CSV into the editorial review queue.';

    /**
     * The external CSV uses the French ECSR headings; our application uses
     * stable slugs so the categories can be renamed without affecting imports.
     *
     * @var array<string, string>
     */
    private const CATEGORY_CODES = [
        'Circulation routière' => 'circulation-routiere',
        'Le conducteur' => 'conducteur',
        'La route' => 'route',
        'Les autres usagers' => 'autres-usagers',
        'Notions diverses' => 'notions-diverses',
        'Premiers secours' => 'premiers-secours',
        'Prendre et quitter son véhicule' => 'prendre-quitter-vehicule',
        'Mécanique et équipements' => 'mecanique-equipements',
        'Sécurité du passager et du véhicule' => 'securite-passager-vehicule',
        'Environnement' => 'environnement',
    ];

    private const REQUIRED_COLUMNS = [
        'id', 'theme', 'question', 'A', 'B', 'C', 'D', 'answer', 'explanation', 'base_rule',
    ];

    private const CODE_REFERENCE = 'https://www.legifrance.gouv.fr/codes/texte_lc/LEGITEXT000006074228/';

    public function handle(TheoryQuestionDeduplicator $deduplicator): int
    {
        $file = realpath((string) $this->argument('file'));
        if ($file === false || ! is_readable($file)) {
            $this->error('CSV file was not found or cannot be read.');

            return self::FAILURE;
        }

        try {
            [$headers, $sourceCount] = $this->inspectCsv($file);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $missingColumns = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));
        if ($missingColumns !== []) {
            $this->error('CSV is missing required columns: '.implode(', ', $missingColumns));

            return self::FAILURE;
        }

        $categoryIds = TheoryCategory::query()
            ->whereIn('code', array_values(self::CATEGORY_CODES))
            ->pluck('id', 'code')
            ->all();

        $missingCategories = array_values(array_diff(array_values(self::CATEGORY_CODES), array_keys($categoryIds)));
        if ($missingCategories !== []) {
            $this->error('Theory categories are not ready. Run the theory migration and starter seed first. Missing: '.implode(', ', $missingCategories));

            return self::FAILURE;
        }

        $batchCode = trim((string) $this->option('batch'));
        $batch = TheoryImportBatch::query()->firstOrCreate(
            ['batch_code' => $batchCode],
            [
                'source' => 'User-provided Code de la Route CSV',
                'target_total' => 4950,
                'source_count' => $sourceCount,
                'status' => 'pending',
                'metadata' => [],
            ],
        );

        $metadata = $batch->metadata ?? [];
        $lastSourceId = (int) ($metadata['last_source_id'] ?? 0);
        $processedRows = (int) ($metadata['processed_source_rows'] ?? 0);
        $limit = max(0, (int) $this->option('limit'));
        $processedThisRun = 0;
        $inserted = 0;
        $duplicates = 0;
        $validationErrors = [];
        $pendingRecords = [];
        $highestSourceId = $lastSourceId;

        $batch->update([
            'source_count' => $sourceCount,
            'status' => 'running',
            'started_at' => $batch->started_at ?? now(),
        ]);

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            $this->error('Unable to open CSV file.');

            return self::FAILURE;
        }

        try {
            $fileHeaders = fgetcsv($handle);
            if ($fileHeaders === false) {
                throw new RuntimeException('CSV does not contain a header row.');
            }
            $fileHeaders[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $fileHeaders[0]);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                if (count($row) !== count($fileHeaders)) {
                    throw new RuntimeException('Malformed CSV row encountered; no incomplete row was imported.');
                }

                /** @var array<string, string> $source */
                $source = array_combine($fileHeaders, $row);
                $sourceId = (int) trim((string) ($source['id'] ?? ''));
                if ($sourceId <= 0) {
                    throw new RuntimeException('Every CSV row requires a positive numeric id.');
                }
                if ($sourceId <= $lastSourceId) {
                    continue;
                }
                if ($limit > 0 && $processedThisRun >= $limit) {
                    break;
                }

                $record = $this->toRecord($source, $categoryIds, $deduplicator);
                if ($record === null) {
                    $validationErrors[] = $sourceId;
                } else {
                    $pendingRecords[] = $record;
                }

                $processedThisRun++;
                $highestSourceId = max($highestSourceId, $sourceId);

                if (count($pendingRecords) === 250) {
                    [$insertedNow, $duplicatesNow] = $this->flush($pendingRecords);
                    $inserted += $insertedNow;
                    $duplicates += $duplicatesNow;
                    $pendingRecords = [];
                }
            }
        } finally {
            fclose($handle);
        }

        if ($pendingRecords !== []) {
            [$insertedNow, $duplicatesNow] = $this->flush($pendingRecords);
            $inserted += $insertedNow;
            $duplicates += $duplicatesNow;
        }

        $processedRows += $processedThisRun;
        $complete = $processedRows >= $sourceCount;
        $metadata['last_source_id'] = $highestSourceId;
        $metadata['processed_source_rows'] = $processedRows;
        $metadata['remaining_in_source_batch'] = max(0, $sourceCount - $processedRows);
        $metadata['invalid_source_ids'] = array_values(array_unique(array_merge($metadata['invalid_source_ids'] ?? [], $validationErrors)));
        $metadata['review_policy'] = 'All user-provided rows stay in draft status until legal and editorial approval.';

        $batch->update([
            'inserted_count' => (int) $batch->inserted_count + $inserted,
            'duplicate_count' => (int) $batch->duplicate_count + $duplicates,
            'review_count' => (int) $batch->review_count + $inserted,
            'metadata' => $metadata,
            'status' => $complete ? 'completed' : 'running',
            'completed_at' => $complete ? now() : null,
        ]);

        $this->table(
            ['batch_code', 'inserted_now', 'duplicates_now', 'invalid_now', 'processed', 'remaining', 'completed'],
            [[
                $batchCode,
                $inserted,
                $duplicates,
                count($validationErrors),
                $processedRows,
                $metadata['remaining_in_source_batch'],
                $complete ? 'yes' : 'no',
            ]],
        );

        return self::SUCCESS;
    }

    /** @return array{0: array<int, string>, 1: int} */
    private function inspectCsv(string $file): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new RuntimeException('CSV does not contain a header row.');
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $rows = 0;
            while (fgetcsv($handle) !== false) {
                $rows++;
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, string> $source
     * @param array<string, string> $categoryIds
     * @return array<string, mixed>|null
     */
    private function toRecord(array $source, array $categoryIds, TheoryQuestionDeduplicator $deduplicator): ?array
    {
        $sourceId = (int) trim($source['id']);
        $categoryCode = self::CATEGORY_CODES[trim($source['theme'])] ?? null;
        $question = trim($source['question']);
        $explanation = trim($source['explanation']);
        $answerLetters = array_values(array_filter(array_map(
            static fn (string $letter): string => trim($letter),
            explode(',', strtoupper($source['answer'])),
        )));

        if ($categoryCode === null || ! isset($categoryIds[$categoryCode]) || $question === '' || $explanation === '' || $answerLetters === []) {
            return null;
        }

        $options = [];
        foreach (['A', 'B', 'C', 'D'] as $index => $letter) {
            $label = trim($source[$letter]);
            if ($label === '') {
                return null;
            }
            $options[] = [
                'id' => (string) Str::uuid(),
                'label' => $label,
                'is_correct' => in_array($letter, $answerLetters, true),
                'sort_order' => $index + 1,
            ];
        }

        if (count(array_filter($options, static fn (array $option): bool => $option['is_correct'])) !== count(array_unique($answerLetters))
            || array_diff($answerLetters, ['A', 'B', 'C', 'D']) !== []) {
            return null;
        }

        return [
            'id' => (string) Str::uuid(),
            'public_id' => 'CDR-CSV-'.str_pad((string) $sourceId, 4, '0', STR_PAD_LEFT),
            'category_id' => $categoryIds[$categoryCode],
            'question_text' => $question,
            'difficulty' => 'medium',
            'answer_type' => count($answerLetters) > 1 ? 'multiple' : 'single',
            'explanation' => $explanation,
            'official_reference' => 'Référence précise à valider (règle source '.$source['base_rule'].') — '.self::CODE_REFERENCE,
            'content_hash' => $deduplicator->hash($question),
            'options' => $options,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{0: int, 1: int}
     */
    private function flush(array $records): array
    {
        $hashes = array_column($records, 'content_hash');
        $publicIds = array_column($records, 'public_id');
        $existing = DB::table('theory_questions')
            ->whereIn('content_hash', $hashes)
            ->orWhereIn('public_id', $publicIds)
            ->get(['content_hash', 'public_id']);
        $existingHashes = $existing->pluck('content_hash')->flip()->all();
        $existingPublicIds = $existing->pluck('public_id')->flip()->all();
        $seenHashes = [];
        $questionRows = [];
        $optionRows = [];
        $duplicates = 0;
        $now = now();

        foreach ($records as $record) {
            if (isset($existingHashes[$record['content_hash']])
                || isset($existingPublicIds[$record['public_id']])
                || isset($seenHashes[$record['content_hash']])) {
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
                'explanation' => $record['explanation'],
                'official_reference' => $record['official_reference'],
                'status' => 'draft',
                'is_active' => false,
                'requires_review' => true,
                'review_notes' => 'Import CSV : validation éditoriale et réglementaire requise avant publication.',
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
