<?php

namespace App\Repository\V2\Student\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchCartRepo
{
    /**
     * @param array|null $attributes
     * @return Model|Builder|null
     */
    public static  function run(User $user = null): Model|Builder|null
    {
        if ($user) {
            $userQuery = $user->student;
        } else {
            $userQuery = auth()->user()->student;
        }
        $cart = $userQuery?->carts()
            ->with(['sale', 'sales', 'cartDetails.offer', 'student.user'])
            ->where('status', CartStatusEnum::PENDING)
            ->latest()
            ->first();

        // if there are multiple sale rows replace the single `sale` relation
        // with an aggregated object containing summed amount/balance etc.
        if ($cart && $cart->relationLoaded('sales') && $cart->sales->count() > 1) {
            $agg = new \stdClass();
            $agg->reference = $cart->sales->pluck('reference')->implode(', ');
            $agg->payment_id = $cart->sales->pluck('payment_id')->filter()->implode(', ');
            $agg->payment_status = $cart->sales->pluck('payment_status')->unique()->count() === 1
                ? $cart->sales->first()->payment_status
                : null;
            $agg->amount = $cart->sales->sum('amount');
            $agg->balance = $cart->sales->sum('balance');
            $cart->setRelation('sale', $agg);
        }

        return $cart;
    }
}
