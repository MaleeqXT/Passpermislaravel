<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchAllTrainingStudentByMonitorRepo;
use App\Repository\V2\Student\Schedule\Training\Job\FetchStudentStatistiqueOfTrainingRepo;
use Inertia\Inertia;
use Inertia\Response;

class MonitorTrainingController extends Controller
{

    /**
     * @param Student $student
     * @return Response
     */
    public function index(Student $student)
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/monitoring/lessons/LessonsPage', [
            'passed' => FetchAllTrainingStudentByMonitorRepo::run(["student_id" => $student?->id, "is_passed" => true]),
            'next' => FetchAllTrainingStudentByMonitorRepo::run(["student_id" => $student?->id, "is_coming" => true]),
            // 'assignedHours' => FetchStudentStatistiqueOfTrainingRepo::run(array_merge(['student_id' => $student->id], request()->all()))->count(),
            'student' => $student->load('user', 'reviewMonitor'),
            'hideBottomBar' => true,
        ]);
    }
}
