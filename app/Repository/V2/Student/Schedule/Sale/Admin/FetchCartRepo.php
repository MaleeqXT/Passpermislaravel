<?php

namespace App\Repository\V2\Student\Schedule\Sale\Admin;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchCartRepo
{
    /**
     * @param Cart $cart
     * @param array|null $attributes
     * @return Model|Builder
     */
    public static function run(Cart $cart, array $attributes = null): Model|Builder
    {
        return $cart->load([ 'cartDetails.offer', 'student.user', 'sale']);
    }
}
