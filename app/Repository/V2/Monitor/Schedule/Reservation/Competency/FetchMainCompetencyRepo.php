<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Competency;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchMainCompetencyRepo
{
    /**
     * Fetch main competencies with associated competencies and ratings for a specific student, applying optional search filters.
     *
     * @param Student $student
     * @param array|null $attributes
     * @return array|Collection
     */
    public static function run(Student $student, array $attributes = null): array|Collection
    {
        $search = $attributes['search'] ?? null;

        return MainCompetency::query()
            ->when($search, fn($query) => self::applySearchFilter($query, $search))
            ->with([
                'competencies',
                'competencies.rating' => fn($query) => $query->where('student_id', $student->id),
            ])
            ->withCount('competencies')
            ->withCount(['competencies as competencies_done_count' => fn($query) => self::applyCompetencyDoneFilter($query, $student)])
            ->orderBy('position')
            ->get();
    }

    /**
     * Apply search filter to the main competency query.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    private static function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->whereRelation('competencies', 'label', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%')
                ->orWhereRelation('competencies.rating', 'comment', 'like', '%' . $search . '%');
        });
    }

    /**
     * Apply filter for counting competencies marked as done.
     *
     * @param Builder $query
     * @param Student $student
     * @return Builder
     */
    private static function applyCompetencyDoneFilter(Builder $query, Student $student): Builder
    {
        return $query->whereHas('rating', fn($query) => $query->where('student_id', $student->id)->where('rating', 3));
    }
}
