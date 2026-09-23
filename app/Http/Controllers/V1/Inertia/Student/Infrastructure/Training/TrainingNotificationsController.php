<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

//use App\Repository\Admin\User\GetAllAdmins;

class TrainingNotificationsController extends Controller
{


    /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/notifications/NotificationsPage', []);
    }
}
