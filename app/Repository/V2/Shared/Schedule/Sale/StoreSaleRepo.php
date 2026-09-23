<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Order\Sale;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreSaleRepo
{
    /**
     * @param array $attributes
     * @param Cart $cart
     * @return Model|Builder
     */
    public static function run(array $attributes, Cart $cart): Model|null
    {
        try {
            $user = auth()->user();
            $ref = 'AD' . now()->format('y') . '-' . $user->id . Str::upper(Str::random(5));
            $saleData = [
                'reference' =>  $ref,
                // The cart belongs to the selected student.  The authenticated user
                // may be an administrator paying from that student's dashboard.
                'student_id' => $cart->student_id,
                'cart_id' => $cart->id,
            ];

            // Preserve installment tracking if provided
            if (isset($attributes['installment_no'])) {
                $saleData['installment_no'] = $attributes['installment_no'];
            }
            if (isset($attributes['total_tranches'])) {
                $saleData['total_tranches'] = $attributes['total_tranches'];
            }

            // If installment info was not passed explicitly, try to extract it from the cart details
            // (some payment flows previously saved it only on cart_details.selected_installment_no)
            if (!isset($saleData['installment_no']) && isset($cart->cartDetails) && $cart->cartDetails->count()) {
                foreach ($cart->cartDetails as $cd) {
                    $sel = $cd->selected_installment_no ?? $cd->selectedInstallmentNo ?? null;
                    $tranches = $cd->tranches ?? null;
                    if ($sel !== null && $sel !== '') {
                        $saleData['installment_no'] = $sel;
                        if (!isset($saleData['total_tranches']) && $tranches) {
                            $saleData['total_tranches'] = $tranches;
                        }
                        break; // use first matching cart detail
                    }
                }
            }

            return Sale::query()->create(array_merge($attributes, $saleData));
        } catch (Exception $e) {
            // Re-throw the exception so callers can handle it and get accurate error context
            info('Failed to Create : ' . $e->getMessage());
            throw $e;
        }
    }
}
