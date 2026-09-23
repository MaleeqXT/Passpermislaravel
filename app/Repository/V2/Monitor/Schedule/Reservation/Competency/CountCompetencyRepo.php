<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Competency;

use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;

class CountCompetencyRepo
{
    /**
     * Count the competencies for a given student with optional filtering.
     *
     * @param Student|null $student
     * @param array|null $attributes
     * @return int
     */
    public static function run(Student $student =null, array $attributes = null): int
    {
        return Competency::query()
            ->when(
                isset($attributes['is_done']),
                fn($query) => $query->whereHas('rating', fn($query) => self::applyRatingFilter($query, $student))
            )
            ->count();
    }

    /**
     * Return the student's generic competency progress.
     *
     * A competency is complete when at least one monitor has assigned rating 3.
     * `whereExists` prevents the same competency being counted more than once
     * when it has ratings from multiple monitors.
     */
    public static function progress(Student $student): array
    {
        $total = Competency::query()
            ->where('status', true)
            ->count();

        $completed = $total === 0 ? 0 : Competency::query()
            ->where('status', true)
            ->whereExists(function ($query) use ($student) {
                $query->selectRaw('1')
                    ->from('ratings')
                    ->whereColumn('ratings.competency_id', 'competencies.id')
                    ->where('ratings.student_id', $student->id)
                    ->where('ratings.rating', 3);
            })
            ->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total === 0 ? 0 : min(100, max(0, (int) round(($completed / $total) * 100))),
        ];
    }

    /**
     * Apply a filter to check if the competency rating is 3 for the given student.
     *
     * @param Builder $query
     * @param Student $student
     * @return Builder
     */
    private static function applyRatingFilter(Builder $query, Student $student): Builder
    {
        return $query->where('student_id', $student->id)
            ->where('rating', 3);
    }
}
