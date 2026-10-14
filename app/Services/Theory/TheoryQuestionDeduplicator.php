<?php

namespace App\Services\Theory;

use App\Models\Theory\TheoryQuestion;
use Illuminate\Support\Str;

class TheoryQuestionDeduplicator
{
    public function hash(string $text): string
    {
        return hash('sha256', $this->normalized($text));
    }

    /**
     * A lightweight editorial safety net. It intentionally flags candidates
     * instead of deleting or merging them automatically: an editor makes the
     * final decision for similarly worded road-safety scenarios.
     */
    public function nearDuplicates(string $text, ?string $ignoreId = null, float $minimum = 0.72): array
    {
        $needle = $this->tokens($text);
        if (count($needle) < 4) {
            return [];
        }

        return TheoryQuestion::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get(['id', 'public_id', 'question_text', 'status'])
            ->map(function (TheoryQuestion $question) use ($needle) {
                $candidate = $this->tokens($question->question_text);
                $union = array_unique(array_merge($needle, $candidate));
                $shared = array_intersect($needle, $candidate);
                $score = count($union) ? count($shared) / count($union) : 0;

                return [
                    'id' => $question->id,
                    'public_id' => $question->public_id,
                    'question_text' => $question->question_text,
                    'status' => $question->status,
                    'similarity' => round($score, 2),
                ];
            })
            ->filter(fn (array $match) => $match['similarity'] >= $minimum)
            ->sortByDesc('similarity')
            ->take(5)
            ->values()
            ->all();
    }

    private function normalized(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/u', ' ', Str::lower(Str::ascii($text)))));
    }

    private function tokens(string $text): array
    {
        $ignored = ['avec', 'dans', 'pour', 'vous', 'sont', 'cette', 'quel', 'quelle', 'apres', 'avant', 'dois', 'peux', 'une', 'des', 'les', 'que', 'sur', 'par', 'est', 'pas'];
        return array_values(array_unique(array_filter(explode(' ', $this->normalized($text)), fn (string $token) => strlen($token) > 2 && ! in_array($token, $ignored, true))));
    }
}
