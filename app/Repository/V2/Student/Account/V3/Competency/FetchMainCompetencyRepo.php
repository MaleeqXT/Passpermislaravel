<?php

namespace App\Repository\V2\Student\Account\V3\Competency;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchMainCompetencyRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(Student $student, array $attributes = null): array|Collection
    {
        $search = $attributes['search'] ?? null;
        return MainCompetency::query()
            // The candidate booklet and the monitor workspace share the same
            // four REMC series.  Older custom groups remain in the database
            // for history, but must not replace the official booklet.
            ->whereIn('label', ['C1', 'C2', 'C3', 'C4'])
            ->where('status', 1)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereRelation('competencies', 'label', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhereRelation('competencies.latestRating', 'comment', 'like', '%' . $search . '%');
                });
            })
            ->with(['competencies' => function ($query) {
                $query->where('status', 1)->orderBy('position');
            }, 'competencies.latestRating' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])
            ->orderBy('position')
            ->get()
            ->values()
            ->take(4)
            ->each(function (MainCompetency $mainCompetency): void {
                $mainCompetency->competencies->each(function ($competency): void {
                    // Preserve the existing public response shape consumed by
                    // the admin React tab, while sourcing it from latestRating.
                    $competency->setRelation('rating', $competency->latestRating);
                    $competency->unsetRelation('latestRating');
                });
            });
    }
}
