<?php

namespace App\Console\Commands;

use App\Models\Theory\TheoryQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebalanceTheoryAnswerOptionOrder extends Command
{
    protected $signature = 'theory:rebalance-option-order {--sources=starter,pdf : Comma-separated sources: starter,pdf,csv}';

    protected $description = 'Rotates answer display order while preserving each question’s correct option content.';

    /** @var array<string, string> */
    private const SOURCE_PATTERNS = [
        'starter' => 'CDR-SEED-%',
        'pdf' => 'CDR-PDF-%',
        'csv' => 'CDR-CSV-%',
    ];

    public function handle(): int
    {
        $sources = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('sources')))));
        $unknown = array_values(array_diff($sources, array_keys(self::SOURCE_PATTERNS)));
        if ($unknown !== []) {
            $this->error('Unknown source(s): '.implode(', ', $unknown));

            return self::FAILURE;
        }

        $summary = [];
        foreach ($sources as $source) {
            $questions = TheoryQuestion::query()
                ->where('public_id', 'like', self::SOURCE_PATTERNS[$source])
                ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('public_id')
                ->get();

            $singleQuestionNumber = 0;
            $reordered = 0;
            $multipleAnswerSkipped = 0;

            DB::transaction(function () use ($questions, &$singleQuestionNumber, &$reordered, &$multipleAnswerSkipped): void {
                foreach ($questions as $question) {
                    $options = $question->options->values();
                    $correctOptions = $options->where('is_correct', true)->values();
                    if ($options->count() < 2 || $correctOptions->count() !== 1) {
                        $multipleAnswerSkipped++;
                        continue;
                    }

                    $correct = $correctOptions->first();
                    $otherOptions = $options->reject(fn ($option) => $option->id === $correct->id)->values();
                    $targetPosition = ($singleQuestionNumber % $options->count()) + 1;
                    $singleQuestionNumber++;
                    $otherIndex = 0;

                    foreach (range(1, $options->count()) as $position) {
                        $option = $position === $targetPosition ? $correct : $otherOptions[$otherIndex++];
                        if ((int) $option->sort_order !== $position) {
                            $option->update(['sort_order' => $position]);
                        }
                    }
                    $reordered++;
                }
            });

            $summary[] = [$source, $questions->count(), $reordered, $multipleAnswerSkipped];
        }

        $this->table(['source', 'questions', 'reordered', 'multiple_answers_skipped'], $summary);

        return self::SUCCESS;
    }
}
