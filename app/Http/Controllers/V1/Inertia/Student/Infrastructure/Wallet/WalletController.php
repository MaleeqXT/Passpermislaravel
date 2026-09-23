<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Wallet;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Job\Dashbord\FetchCoursRepo;
use Inertia\Inertia;
use Inertia\Response;

//use App\Repository\Admin\User\GetAllAdmins;

class WalletController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/dashboard/DashboardPage', [
            'lessons' => FetchCoursRepo::run(request()->all()),
        ]);
    }
}
