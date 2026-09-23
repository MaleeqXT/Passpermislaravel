<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use Illuminate\Database\Eloquent\Collection;

class FetchLastSaleRepo
{
    /**
     * @param array|null $attributes
     * @return Collection
     */
    public static function run(array $attributes = null): Collection
    {
        return Sale::query()
            ->with('student', 'student.user', 'cart', 'cart.cartDetails')
            ->where('payment_status', SaleStatusEnum::PAID->value)
            ->when(!empty($attributes['zone_id']), fn ($query) => $query->whereHas('student.user', fn ($userQuery) => $userQuery->where('zone_id', $attributes['zone_id'])))
            ->orderByDesc('created_at')
            ->take(3)
            ->get();
    }


}
