<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Monitor\Billing\FetchAllHourInvoiceRepo;
use App\Repository\V2\Monitor\Billing\FetchAllInvoiceRepo;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{

    /** Billing list for the administration billing table. */
    public function index(): JsonResponse
    {
        return response()->json(FetchAllInvoiceRepo::run(request()->all()));
    }

    /**
     * @param Monitor $monitor
     * @param Billing|null $billing
     * @return JsonResponse
     */
    public function getResumeHours(Monitor $monitor, ?Billing $billing): JsonResponse
    {
        return response()->json([
            'data' => FetchAllHourInvoiceRepo::run($monitor, $billing, request()->all())
        ]);
    }

    /**
     * @param Billing $billing
     * @return JsonResponse
     */
    public function hourRapport2(Billing $billing): JsonResponse
    {
        return response()->json([
            'data' => FetchAllHourInvoiceRepo::run(auth()->user()?->monitor, $billing, request()->all())
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function rapport(): JsonResponse
    {
        return response()->json(FetchAllHourInvoiceRepo::run(auth()->user()?->monitor, null, request()->all()));
    }
}
