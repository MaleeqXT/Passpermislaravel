<?php

namespace App\Repository\V2\Shared\Base\Billing\Strip;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class ChargeStripeClientRepo
{
    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.api_keys.secret_key'));
    }

    /**
     * @param array $attributes
     * @param Sale $sale
     * @return PaymentIntent
     * @throws Exception
     */

    public function run(array $attributes, Sale $sale)
{
    try {
        if (!$sale || !$sale->id) {
            throw new Exception('Invalid sale provided to ChargeStripeClientRepo');
        }

        // Always ensure we are working with a clean float
        $amountInEuros = (float) $attributes['amount'];

        // Convert to cents safely
        $amountInCents = (int) round($amountInEuros * 100);

        Log::info('✅ FINAL STRIPE PAYMENT', [
            'amount_euros' => $amountInEuros,
            'amount_cents' => $amountInCents,
        ]);

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'always'
            ],
            'description' => 'AD Payment.',
            'metadata' => [
                'sale_id' => $sale->id,
                'balance' => $attributes['balance'],
                'student_id' => auth()->user()->student->id ?? null,
                'user' => auth()->user()->name ?? null,
            ],
        ]);

        return $paymentIntent;

    } catch (Exception $e) {
        Log::error('Stripe PaymentIntent Creation Failed:', [
            'error' => $e->getMessage()
        ]);
        throw new Exception($e->getMessage());
    }
}
}
