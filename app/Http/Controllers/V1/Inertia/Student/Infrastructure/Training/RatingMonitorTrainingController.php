<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\FetchAllReviewMonitorRepo;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RatingMonitorTrainingController extends Controller
{

    /**
     * @param Student $student
     * @return Response
     */
    public function index(Student $student): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/evaluations/reviews/ReviewsPage', [
            'student' => $student->load('user'),
            'hideBottomBar' => true
        ]);
    }
    /**
     * @param ReviewMonitor $reviewMonitor
     * @return Response
     */
    public function show(ReviewMonitor $reviewMonitor): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/evaluations/reviews/ReviewSinglePage', [
            'review' => $reviewMonitor->load('reservation.monitortraining.student.user'),
            'hideBottomBar' => true
        ]);
    }
}
