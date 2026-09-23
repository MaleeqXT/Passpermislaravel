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
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereRelation('competencies', 'label', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->whereRelation('competencies.rating', 'comment', 'like', '%' . $search . '%');
                });
            })
            ->with(['competencies.rating' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])
            ->orderBy('position')
            ->get();
    }
}
