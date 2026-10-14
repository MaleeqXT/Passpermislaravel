<?php

namespace Database\Seeders\Theory;

use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheorySeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds reusable question pools without creating, editing, or duplicating a
 * question record. Students receive a random set of 40 from the pool.
 */
class TheorySeriesSeeder extends Seeder
{
    private const QUESTIONS_PER_SERIES_POOL = 200;

    public function run(): void
    {
        $questions = TheoryQuestion::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('public_id')
            ->pluck('id')
            ->all();

        if (count($questions) < self::QUESTIONS_PER_SERIES_POOL) {
            $this->command?->warn('Au moins 200 questions publiées sont nécessaires pour préparer les pools de séries.');
            return;
        }

        $series = collect();
        foreach ([
            'training' => ['prefix' => 'Série d’entraînement', 'count' => 30, 'duration' => null],
            'exam' => ['prefix' => 'Examen blanc', 'count' => 10, 'duration' => 40],
        ] as $type => $config) {
            for ($number = 1; $number <= $config['count']; $number++) {
                $series->push(TheorySeries::firstOrCreate(
                    ['type' => $type, 'title' => $config['prefix'].' '.$number],
                    [
                        'status' => 'published',
                        'duration_minutes' => $config['duration'],
                        'sort_order' => $number,
                    ]
                ));
            }
        }

        foreach ($series as $item) {
            $existing = DB::table('theory_series_questions')
                ->where('series_id', $item->id)
                ->orderBy('sort_order')
                ->get(['question_id', 'sort_order']);
            $missing = self::QUESTIONS_PER_SERIES_POOL - $existing->count();
            if ($missing <= 0) {
                continue;
            }

            $assignedIds = $existing->pluck('question_id')->all();
            $available = collect($questions)
                ->reject(fn (string $questionId) => in_array($questionId, $assignedIds, true))
                ->sortBy(fn (string $questionId) => hash('sha256', $item->id.$questionId))
                ->values();
            $nextOrder = ((int) $existing->max('sort_order')) + 1;
            $rows = [];
            for ($index = 0; $index < $missing && isset($available[$index]); $index++) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'series_id' => $item->id,
                    'question_id' => $available[$index],
                    'sort_order' => $nextOrder + $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) {
                DB::table('theory_series_questions')->insert($rows);
            }
        }
    }
}
