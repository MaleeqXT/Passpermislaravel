<?php

namespace App\Repository\V2\Shared\Base\Billing\Strip;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;
use Stripe\StripeClient;

class RefundStripeClientRepo
{

    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.api_keys.secret_key'));
    }

    /**
     * @param Sale $sale
     * @param int|null $amount
     * @return Refund
     * @throws ApiErrorException
     */

    public  function run(Sale $sale, int $amount = null)
    {
        return $this->stripe->refunds->create([
            'charge' => $sale->payment_id,
            'amount' => ($amount ?? $sale->amount) * 100,
            'metadata' => [
                'sale_id' => $sale->id,
                'student_id' => $sale->student_id,
                'admin' => auth()->user()->name,
            ]
        ]);
    }
}
