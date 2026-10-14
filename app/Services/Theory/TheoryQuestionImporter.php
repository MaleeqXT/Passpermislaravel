<?php

namespace App\Services\Theory;

use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Models\Theory\TheoryQuestion;
use Illuminate\Support\Facades\DB;

class TheoryQuestionImporter
{
    /**
     * Imports a named, deterministic batch. The processed public IDs are kept
     * on the batch, so a timed-out Artisan run continues exactly where it
     * stopped instead of duplicating content on its next run.
     */
    public function import(string $batchCode, array $categories, array $records, int $limit, int $target = 4950): array
    {
        $categoryIds = [];
        foreach ($categories as $category) {
            $model = TheoryCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                $category
            );
            $categoryIds[$model->code] = $model->id;
        }

        $batch = TheoryImportBatch::query()->firstOrCreate(
            ['batch_code' => $batchCode],
            [
                'source' => 'PassPermisFacile original editorial batch',
                'target_total' => $target,
                'source_count' => count($records),
                'status' => 'pending',
                'metadata' => ['processed_public_ids' => []],
            ]
        );

        $metadata = $batch->metadata ?? [];
        $processed = $metadata['processed_public_ids'] ?? [];
        $remaining = array_values(array_filter($records, fn (array $record) => ! in_array($record['public_id'], $processed, true)));
        $slice = array_slice($remaining, 0, max(1, $limit));
        $inserted = 0;
        $duplicates = 0;
        $review = 0;

        $batch->update(['status' => 'running', 'started_at' => $batch->started_at ?? now()]);

        foreach ($slice as $record) {
            $hash = app(TheoryQuestionDeduplicator::class)->hash($record['question_text']);
            $exists = TheoryQuestion::withTrashed()->where('content_hash', $hash)->exists();
            if ($exists) {
                $duplicates++;
                $processed[] = $record['public_id'];
                continue;
            }

            $nearMatches = app(TheoryQuestionDeduplicator::class)->nearDuplicates($record['question_text']);
            $requiresReview = (bool) ($record['requires_review'] ?? false) || count($nearMatches) > 0;
            $status = $requiresReview ? 'draft' : ($record['status'] ?? 'published');
            $review += $requiresReview ? 1 : 0;

            DB::transaction(function () use ($record, $categoryIds, $hash, $requiresReview, $nearMatches, $status) {
                $question = TheoryQuestion::create([
                    'public_id' => $record['public_id'],
                    'category_id' => $categoryIds[$record['category_code']],
                    'question_text' => $record['question_text'],
                    'difficulty' => $record['difficulty'],
                    'answer_type' => $record['answer_type'],
                    'media_type' => $record['media_type'] ?? null,
                    'media_path' => $record['media_path'] ?? null,
                    'explanation' => $record['explanation'],
                    'official_reference' => $record['official_reference'],
                    'status' => $status,
                    'is_active' => $status === 'published',
                    'requires_review' => $requiresReview,
                    'review_notes' => $requiresReview && count($nearMatches) ? 'Ressemblance rédactionnelle à vérifier avant publication.' : ($record['review_notes'] ?? null),
                    'content_hash' => $hash,
                ]);

                foreach ($record['options'] as $index => $option) {
                    $question->options()->create([
                        'label' => $option['label'],
                        'is_correct' => $option['is_correct'],
                        'sort_order' => $index + 1,
                    ]);
                }
            });

            $inserted++;
            $processed[] = $record['public_id'];
        }

        $processed = array_values(array_unique($processed));
        $complete = count($processed) >= count($records);
        $metadata['processed_public_ids'] = $processed;
        $metadata['remaining_in_source_batch'] = max(0, count($records) - count($processed));
        $batch->update([
            'source_count' => count($records),
            'inserted_count' => (int) $batch->inserted_count + $inserted,
            'duplicate_count' => (int) $batch->duplicate_count + $duplicates,
            'review_count' => (int) $batch->review_count + $review,
            'metadata' => $metadata,
            'status' => $complete ? 'completed' : 'running',
            'completed_at' => $complete ? now() : null,
        ]);

        return [
            'batch_code' => $batchCode,
            'inserted_now' => $inserted,
            'duplicates_now' => $duplicates,
            'review_now' => $review,
            'processed' => count($processed),
            'remaining' => max(0, count($records) - count($processed)),
            'completed' => $complete,
        ];
    }
}
