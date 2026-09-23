<?php

namespace App\Repository\V2\Shared\Base\Billing\Paypal;

use Psr\Http\Message\StreamInterface;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;

class PayPalClientRepo
{

    /**
     * @param array $attributes
     * @return array|StreamInterface|string
     * @throws Throwable|\Throwable
     */
    public  function run(array $attributes): StreamInterface|array|string
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();
        return $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('api.paypal.transaction.success'),
                "cancel_url" => route('api.paypal.transaction.cancel'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $attributes['amount']
                    ]
                ]
            ]
        ]);
    }
}
