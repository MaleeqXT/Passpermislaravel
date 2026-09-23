<?php

namespace App\Repository\V2\Shared\Competency;

use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FetchCompetencyRepo
{
    /**
     * @param MainCompetency $mainCompetency
     * @param array|null $attributes
     * @return Collection|Builder
     */
    public static function run(MainCompetency $mainCompetency, array $attributes = null): array|Collection
    {
        $search = $attributes['search'] ?? null;

        return Competency::query()
            ->where('main_competency_id', $mainCompetency->id)
             ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes['status']))
            ->when($search, fn($query) => self::applySearchFilter($query, $search))
            ->orderBy('position', 'asc')
            ->get();
    }


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
            $query->where('label', 'like', '%' . $search . '%');
        });
    }
}
