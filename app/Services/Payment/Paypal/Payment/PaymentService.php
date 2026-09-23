<?php

namespace App\Services\Payment\Paypal\Payment;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletBalanceTypeEnum;
use App\Repository\V2\Shared\Schedule\Sale\EditSaleRepo;
use App\Repository\V2\Shared\Schedule\Sale\StoreSaleRepo;
use App\Repository\V2\Student\Schedule\Sale\Admin\EditCartRepo;
use App\Repository\V2\Student\Schedule\Sale\FetchCartRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\StoreOrEditWalletAction;
use App\Repository\V2\Student\Schedule\Training\Wallet\CalculateInstallmentBalanceAction;
use App\Repository\V2\Student\User\EditStudentRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;

class PaymentService implements PaymentInterface
{


    /**
     * @param array $attributes
     * @return JsonResponse|RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception|Throwable
     */
    public function processTransaction(array $attributes): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();
        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $paypalToken = $provider->getAccessToken();
            $response = $provider->createOrder([
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
            if (isset($response['id']) && $response['id'] != null) {
                // redirect to approve href
                foreach ($response['links'] as $links) {
                    if ($links['rel'] == 'approve') {
                        $cart = FetchCartRepo::run();

                        // Extract installment info from cart details
                        $cartDetail = $cart->cartDetails->first();
                        $installmentNo = $cartDetail?->selected_installment_no ?? $cartDetail?->selectedInstallmentNo ?? null;
                        $tranches = $cartDetail?->tranches ?? 0;

                        $saleAttrs = Arr::only($attributes, ['amount', 'balance']);
                        $saleAttrs['payment_method'] = SalePaymentMethodEnum::PAYPAL->value;

                        // Pass installment tracking if this is a multi-payment purchase
                        if ($tranches > 1 && $installmentNo) {
                            $saleAttrs['installment_no'] = $installmentNo;
                            $saleAttrs['total_tranches'] = $tranches;
                        }

                        StoreSaleRepo::run($saleAttrs, $cart);

                        DB::commit();
                        session()->flash('success', 'Payment on Transaction.');
                        return response()->json([
                            'approval_link' => $links['href'],
                        ]);
                    }
                }
                return redirect()
                    ->route('payment')
                    ->with('error', 'Something went wrong.');
            } else {
                return redirect()
                    ->route('panel.index')
                    ->with('error', $response['message'] ?? 'Something went wrong.');
            }
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    /**
     * @throws Throwable
     */
    public function successTransaction(array $attributes): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();
            $response = $provider->capturePaymentOrder($attributes['token']);
            if (isset($response['status']) && $response['status'] == 'COMPLETED') {
                $cart = FetchCartRepo::run();
                if (empty($cart))
                    throw new Exception('Cart not found');

                foreach ($cart->cartDetails as $cartDetail) {
                    $offer = $cartDetail->offer;
                    $tranches = $cartDetail->tranches ?? 0;
                    $selectedInstallmentNo = $cartDetail->selected_installment_no ?? $cartDetail->selectedInstallmentNo ?? null;

                    // Skip if this cart detail doesn't have an installment selected (not being paid in this transaction)
                    if ($tranches > 1 && !$selectedInstallmentNo) {
                        continue;
                    }

                    // Determine balance type and calculate balance (mirrors earlier logic)
                    if ($tranches > 1) {
                        $balance = CalculateInstallmentBalanceAction::run($offer, $tranches);
                        $balanceType = WalletBalanceTypeEnum::INSTALLMENT->value;
                    } else {
                        $balance = $offer->balance ?? $offer->final_price ?? $offer->original_price ?? 0;
                        $balanceType = WalletBalanceTypeEnum::FULL->value;
                    }

                    StoreOrEditWalletAction::run(auth()->user()->student, [
                        'balance' => $balance,
                        'status' => WalletTypeEnum::ACTIVE->value,
                        'offer_id' => $cartDetail->offer_id,
                        'balance_type' => $balanceType,
                        'installment_no' => $selectedInstallmentNo, // Track which installment
                        'total_tranches' => $tranches, // Track total installments
                        'force_create' => true, // Always create new wallet row for installments
                    ]);

                    // we no longer clear `selected_installment_no` here; the
                    // database may not have the column and the frontend handles its
                    // own state.  leaving the row untouched avoids SQL errors.
                }

                // mark all sale rows for cart as paid
                $relatedSales = Sale::query()->where('cart_id', $cart->id)->get();
                $totalBalance = $relatedSales->sum('balance');

                EditStudentRepo::run(auth()->user()->student, [
                    'balance' => $totalBalance + auth()->user()?->student?->balance
                ]);

                foreach ($relatedSales as $rs) {
                    EditSaleRepo::run($rs, [
                        'payment_status' => SaleStatusEnum::PAID->value
                    ]);
                }

                EditCartRepo::run($cart, [
                    'status' => CartStatusEnum::PAID->value
                ]);

                DB::commit();
                return redirect()
                    ->route('student.shop.index')
                    ->with('success', 'Transaction complete.');
            } else {
                return redirect()
                    ->route('api.paypal.transaction.process')
                    ->with('error', $response['message'] ?? 'Something went wrong.');
            }
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
