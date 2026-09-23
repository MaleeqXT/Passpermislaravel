<?php

namespace App\Repository\V2\Shared\Base\Billing\Strip;

use Exception;
use Stripe\Charge;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

class TokenStripeClientAction
{

    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.api_keys.secret_key'));
    }

    /**
     * @param array|null $cardData
     * @return array|Charge
     */
    public function run(array $cardData = null): array|Charge
    {
        $token = [];
        try {
            $token = $this->stripe->tokens->create([
                'card' => [
                    'number' => $cardData['cardNumber'],
                    'exp_month' => $cardData['month'],
                    'exp_year' => $cardData['year'],
                    'cvc' => $cardData['cvv']
                ]
            ]);
        } catch (CardException $e) {
            $token = ['error' => $e->getError()->message];
        } catch (Exception $e) {
            $token = ['error' => $e->getMessage()];
        }
        return $token;
    }
}
