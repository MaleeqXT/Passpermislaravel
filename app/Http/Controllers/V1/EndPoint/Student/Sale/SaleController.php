<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\Sale\Strip\PaymentRequest;
use App\Services\Payment\Strip\Payment\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{

    /**
     * success response method.
     *
     * @param PaymentRequest $request
     * @param PaymentService $service
     * @return JsonResponse|RedirectResponse
     * @throws Exception
     */

    public function store(PaymentRequest $request, PaymentService $service): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();
        try {

            $status = $service->action($request->validated());

            DB::commit();
            return response()->json([
                'message' => $status
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
