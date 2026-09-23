<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Student\Account\V3\History\FetchAllTrainingRepo;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InfoHourStudentController extends Controller
{

    /**
     * @param Student $student
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Student $student): JsonResponse
    {
        return response()->json(['data' => FetchAllTrainingRepo::run($student, request()->all())]);
    }
}
