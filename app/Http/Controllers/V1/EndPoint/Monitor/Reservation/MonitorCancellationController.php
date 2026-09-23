<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\Reservation;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Cancellation\FetchAllCancellationRepo;
use Illuminate\Http\JsonResponse;

class MonitorCancellationController extends Controller
{

    /**
     *   /**
     * @return Response
     */
    public function index(): JsonResponse
    {
        return response()->json(FetchAllCancellationRepo::run(request()->all()));
    }
}
