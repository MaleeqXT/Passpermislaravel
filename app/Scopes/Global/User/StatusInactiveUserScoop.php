<?php

namespace App\Scopes\Global\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StatusInactiveUserScoop implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param User|Model $user
     * @return void
     */
    public function apply(Builder $builder, User|Model $user): void
    {
        $user->where('status', SituationStatusEnum::ACTIVE->value);
    }
}
