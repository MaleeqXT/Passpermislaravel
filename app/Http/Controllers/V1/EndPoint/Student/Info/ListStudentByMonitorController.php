<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\UpdateAccountStudentRequest;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Student\User\EditStudentRepo;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ListStudentByMonitorController extends Controller
{


    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
public function getAllEleves(): JsonResponse
{
    // Get all students with their progress and training stats (no filters)
    $students = \App\Models\User::withoutGlobalScopes()
    ->where('zone_id',auth()->user()->zone_id)
        ->whereHas('student')
        ->with([
            'student' => function ($query) {
                $query->withCount([
                    'ratings as progress_done' => fn($q) => $q->where('rating', 3),
                    'trainings as balance_used',
                ])->with('reviewMonitor');
            }
        ])
        ->orderBy('name', 'asc')
        ->get();

    return response()->json([
        'data' => $students,
        'total' => $students->count(),
    ]);
}



    /**
     * @param Student $student
     * @param UpdateAccountStudentRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateEleve(Student $student, UpdateAccountStudentRequest $request): JsonResponse
    {
        return response()->json(['status' => EditStudentRepo::run($student, $request->validated())]);
    }
}
