<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FetchSaleRepo
{
    /**
     * @param Sale|string $sale
     * @param array|null $attributes
     * @return Model|Builder
     */
    public static function run(Sale|string $sale, array $attributes = null): Model|Builder
    {
        try {
            if ($sale instanceof Model) {

                return $sale
                    ->when(auth()->user()?->student, function ($query) {
                        $query->where('student_id', auth()->user()?->student->id);
                    })
                    ->with('cart.cartDetails.offre', 'student.user')
                    ->first();
            }
            return Sale::query()
                ->when(auth()->user()?->student, function ($query) {
                    $query->where('student_id', auth()->user()?->student->id);
                })
                ->findOrFail($sale);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create : ' . $e->getMessage());
            return false;
        }

    }
}
