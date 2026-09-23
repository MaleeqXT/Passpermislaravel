<?php

namespace App\Repository\V2\Monitor\Evaluation;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\User\Informations\Billing;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FetchDocumentEvaluationRepo
{
    /**
     * Fetch a paginated list of Billing records with optional filtering.
     *
     * @param array $attributes Filtering attributes.
     * @return LengthAwarePaginator The paginated result.
     */
    public static function run(array $attributes = [], array $with = [])
    {
        return DocumentEvaluation::query()
            ->with(['monitor', 'student'])
            ->with($with)
            ->when(auth()->user()?->monitor?->id ?? isset($attributes['monitor_id']), fn($query) => self::applyMonitorFilter($query, $attributes))
            ->when(auth()->user()?->student?->id ?? isset($attributes['student_id']), fn($query) => self::applyStudentFilter($query, $attributes))
            ->first();
    }

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyMonitorFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('monitor_id', auth()->user()?->monitor?->id ?? $attributes['monitor_id']);
    }

    /**
     * Apply monitor filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('student_id', auth()->user()?->student?->id ?? $attributes['student_id']);
    }
}
