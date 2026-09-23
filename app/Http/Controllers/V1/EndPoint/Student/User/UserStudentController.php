<?php

namespace App\Http\Controllers\V1\EndPoint\Student\User;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class UserStudentController extends Controller
{

    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllEleves(): JsonResponse
    {
        return response()->json(FetchAllStudentRepo::run(request()->all()));
    }
}
