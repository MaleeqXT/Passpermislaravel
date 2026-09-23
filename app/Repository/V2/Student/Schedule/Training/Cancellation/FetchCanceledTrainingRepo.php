<?php

namespace App\Repository\V2\Student\Schedule\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchCanceledTrainingRepo
{
    /**
     * @param array $attributes
     * @param int $days
     * @return Builder|Model|object
     */
    public static function run(array $attributes, int $days = 0): Builder|Model|null
    {
        return Training::query()
            ->where('id', $attributes['training_id'])
            ->where(function (Builder $query) use ($days) {
                $query->whereRelation('reservation', 'date', '>=', now()->addDays($days))
                    ->orWhereRelation('offer', 'is_cpf', true);
            })
            ->first();
    }
}
