<?php

namespace App\Services\Payment\Paypal\Payment;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

interface PaymentInterface
{

    /**
     * @param array $attributes
     * @return JsonResponse|RedirectResponse
     */
    public function processTransaction(array $attributes): JsonResponse|RedirectResponse;

    public function successTransaction(array $attributes): RedirectResponse;
}
