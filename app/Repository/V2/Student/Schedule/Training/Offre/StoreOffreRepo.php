<?php

namespace App\Repository\V2\Student\Schedule\Training\Offre;

use App\Models\Roles\Admin\Offer\Offer;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreOffreRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */

    public static function run(array $attributes): Model|Builder
    {
        try {
            // 🔧 STEP 0: propagate agency_pricing values into top‑level columns
            if (isset($attributes['agency_pricing'])) {
                $pricing = is_string($attributes['agency_pricing'])
                    ? json_decode($attributes['agency_pricing'], true)
                    : $attributes['agency_pricing'];

                if (is_array($pricing) && count($pricing) > 0) {
                    // prefer the "criel" agency, otherwise take the first element
                    $entry = collect($pricing)->firstWhere('agency', 'criel') ?? $pricing[0];

                    // copy any fields that are also columns on the offers table
                    foreach ([
                        'price_ht',
                        'original_price',
                        'discounted_price',
                        'second_price',
                        'balance',
                        'balance_2',
                        'multi_payment',
                        'total_payment',
                        'agency_name',
                    ] as $field) {
                        if (array_key_exists($field, $entry)) {
                            $attributes[$field] = $entry[$field];
                        }
                    }

                    // ensure agency_name is set when only agency key present
                    $attributes['agency_name'] = $entry['agency'] ?? $attributes['agency_name'] ?? null;
                }

            }

            // ✅ STEP 1: Handle total payment + multi payment logic
            $total = $attributes['total_payment'] ?? null;
            $multi = $attributes['multi_payment'] ?? 1;

            if ($total && $multi > 1) {
                $installmentAmount = round($total / $multi, 2);
                $installments = [];

                for ($i = 1; $i <= $multi; $i++) {
                    $installments[] = [
                        'installment_no' => $i,
                        'amount' => $installmentAmount,
                        'status' => 'pending',
                    ];
                }

                $attributes['installments_data'] = $installments;

            }

            // ✅ STEP 2: Create the Offer
            return Offer::query()->create($attributes);

        } catch (Exception $e) {
            // Log the exception message for debugging
            info('Failed to store offer: ' . $e->getMessage());
            throw $e;
        }
    }



}
