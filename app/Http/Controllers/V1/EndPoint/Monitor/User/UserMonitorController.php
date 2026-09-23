<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor\User;

use App\Http\Controllers\Controller;
use App\Repository\V2\Monitor\User\FetchAllMonitorRepo;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserMonitorController extends Controller
{

    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllMoniteurs(): JsonResponse
    {
        return response()->json(FetchAllMonitorRepo::run(request()->all()));
    }
}
