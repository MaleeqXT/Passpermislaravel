<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure;

use App\Http\Controllers\Controller;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Monitor\User\FetchMonitorRepo;
use Inertia\Inertia;
use Inertia\Response;

class StudentListController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-monitor');
        // $progress_total = CountCompetencyRepo::run();
        return Inertia::render('features/monitoring/students/StudentsPage', [
            // 'progressTotal' => $progress_total
        ]);
    }
}
