<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale\Type;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\Sale\Strip\PaymentRequest;
use App\Services\Payment\Paypal\Payment\PaymentInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;


class PaypalController extends Controller
{


    public function __construct(public PaymentInterface $service) {}

    //Account sb-o6vj427592340@personal.example.com  c/3zW9vr

    /**
     * process transaction.
     *
     * @throws Throwable
     */
    public function processTransaction(PaymentRequest $request)
    {
        return $this->service->processTransaction($request->validated());
    }

    /**
     * success transaction.
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function successTransaction(Request $request): Response|RedirectResponse
    {
        return $this->service->successTransaction($request->all());
    }

    /**
     * cancel transaction.
     *
     * @return RedirectResponse|Response
     */
    public function cancelTransaction(Request $request)
    {
        $cancelData = $request->all();
        return redirect()
            ->route('student.shop.index', $cancelData)
            ->with('error', $response['message'] ?? 'You have canceled the transaction.');
    }
}
