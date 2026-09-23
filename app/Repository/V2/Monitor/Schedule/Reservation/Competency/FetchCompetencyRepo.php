<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Competency;

use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchCompetencyRepo
{
    /**
     * Fetch competencies for a given main competency with optional search and student rating filter.
     *
     * @param MainCompetency $mainCompetency
     * @param array|null $attributes
     * @return array|Collection
     */
    public static function run(MainCompetency $mainCompetency, array $attributes = null): array|Collection
    {
        $search = $attributes['search'] ?? null;

        return Competency::query()
            ->where('main_competency_id', $mainCompetency->id)
            ->when($search, fn($query) => self::applySearchFilter($query, $search))
            ->with(['rating' => fn($query) => $query->where('student_id', $attributes['student_id'] ?? null)])
            ->get();
    }

    /**
     * Apply search filter to the competency query.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    private static function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('label', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%')
                ->orWhereRelation('rating', 'comment', 'like', '%' . $search . '%');
        });
    }
}
