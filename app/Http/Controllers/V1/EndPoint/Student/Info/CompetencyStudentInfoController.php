<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Student\Account\V3\Competency\FetchMainCompetencyRepo;
use Illuminate\Http\JsonResponse;

class CompetencyStudentInfoController extends Controller
{

    /**
     * @param Student $student
     * @return JsonResponse
     */
    public function index(Student $student): JsonResponse
    {
        return response()->json(['data' => FetchMainCompetencyRepo::run($student, request()->all())]);
    }
}
