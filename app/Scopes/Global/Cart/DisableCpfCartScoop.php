<?php

namespace App\Scopes\Global\Cart;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DisableCpfCartScoop implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('status', '!=', CartStatusEnum::CPF->value);
    }
}
