<?php

namespace App\Http\Controllers\V1\EndPoint\System\User;

use App\Http\Controllers\Controller;
use App\Repository\V2\Admin\User\FetchAllRepo;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class UserAdminController extends Controller
{

    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllOperators(): JsonResponse
    {
        return response()->json(FetchAllRepo::run(request()->all()));
    }
}
