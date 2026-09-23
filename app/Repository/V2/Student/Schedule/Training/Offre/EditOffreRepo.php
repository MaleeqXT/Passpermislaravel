<?php

namespace App\Repository\V2\Student\Schedule\Training\Offre;

use App\Models\Roles\Admin\Offer\Offer;
use Exception;

class EditOffreRepo
{
    /**
     * @param Offer $offer
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public static function run(Offer $offer, array $attributes): bool

    {
        // propagate agency_pricing into the main fields if provided
        if (isset($attributes['agency_pricing'])) {
            $pricing = is_string($attributes['agency_pricing'])
                ? json_decode($attributes['agency_pricing'], true)
                : $attributes['agency_pricing'];

            if (is_array($pricing) && count($pricing) > 0) {
                $entry = collect($pricing)->firstWhere('agency', 'criel') ?? $pricing[0];

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

                $attributes['agency_name'] = $entry['agency'] ?? $attributes['agency_name'] ?? null;
            }

        }

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

        try {
            return $offer->update($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed : ' . $e->getMessage());
            throw $e;
        }

    }
}
