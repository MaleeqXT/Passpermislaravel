<?php

namespace App\Http\Controllers\V1\Inertia\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchByTodayAllMonitorReservationRepo;
use Inertia\Inertia;
use Inertia\Response;

class HomePageController extends Controller
{


    /**
     * @return Response
     */
    public function index()
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/dashboard/DashboardPage', [
            // 'reservations' => FetchByTodayAllMonitorReservationRepo::run(request()->all(), ['training.student.reviewMonitor']),
        ]);
    }

    // /**
    //  * @return Response
    //  */
    // public function availability()
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/dashboard/AvailabilityPage', [
    //         'reservations' => FetchByTodayAllMonitorReservationRepo::run(request()->all()),
    //         'hideBottomBar' => true
    //     ]);
    // }

    // /**
    //  * @param Student $student
    //  * @return Response
    //  */
    // public function competences(Student $student)
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/dashboard/CompetencesPage', [
    //         'student' => $student->load('user'),
    //         'hideBottomBar' => true
    //     ]);
    // }
}
