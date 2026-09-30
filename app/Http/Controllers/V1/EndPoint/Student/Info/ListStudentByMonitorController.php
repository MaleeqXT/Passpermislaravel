<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Info;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\UpdateAccountStudentRequest;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Student\User\EditStudentRepo;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ListStudentByMonitorController extends Controller
{


    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
public function getAllEleves(Request $request): JsonResponse
{
    $user = $request->user();
    $monitor = $user?->monitor
        ?? Monitor::query()->where('user_id', $user?->id)->first();

    // Administrators viewing a monitor dashboard may inspect that monitor's
    // students, but the result must still be scoped to that one monitor.
    if (!$monitor && $request->filled('monitor_id') && $user?->hasAnyRole(['admin', 'super-admin'])) {
        $monitor = Monitor::query()
            ->whereKey($request->input('monitor_id'))
            ->orWhere('user_id', $request->input('monitor_id'))
            ->first();
    }

    if (!$monitor) {
        return response()->json(['data' => [], 'total' => 0]);
    }

    // A student belongs in this list only when at least one of their trainings
    // is reserved with the selected monitor. whereHas + distinct makes the
    // relationship the source of truth and prevents duplicate students.
    $students = \App\Models\User::withoutGlobalScopes()
        ->whereHas('student.trainings.reservation', fn ($query) => $query->where('monitor_id', $monitor->id))
        ->with([
            'student' => function ($query) {
                $query->withCount([
                    'ratings as progress_done' => fn($q) => $q->where('rating', 3),
                    'trainings as balance_used',
                ])->with('reviewMonitor');
            }
        ])
        ->orderBy('name', 'asc')
        ->distinct()
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
