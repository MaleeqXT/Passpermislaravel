<?php

namespace App\Http\Controllers\Backend\Front;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        Inertia::setRootView('front');
        return Inertia::render('features/dashboard/DashboardPage');
    }
}
