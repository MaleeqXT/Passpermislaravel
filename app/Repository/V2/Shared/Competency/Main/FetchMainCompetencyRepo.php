<?php

namespace App\Repository\V2\Shared\Competency\Main;

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchMainCompetencyRepo
{
    /**
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(array $attributes = null): array|Collection
    {
        // dd($attributes['status']);
        $search = $attributes['search'] ?? null;
        

        return MainCompetency::query()->where('zone_id',auth()->user()->zone_id)
            ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes['status']))
            ->when($search, fn($query) => self::applySearchFilter($query, $search))
            ->orderBy('position', 'asc')
            ->get();
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param string $status
     * 
     */
    private static function applyStatusFilter(Builder $query, string $status)
    {   
         if ($status == "inactive"){
            return $query->where('status', 0);
        }

        return $query->where('status', 1);;
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    private static function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereRelation('competencies', 'label', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->whereRelation('competencies.rating', 'comment', 'like', '%' . $search . '%');
            })
                ->orWhere('name', 'like', '%' . $search . '%')
                ->orWhere('label', 'like', '%' . $search . '%');
        });
    }
}
