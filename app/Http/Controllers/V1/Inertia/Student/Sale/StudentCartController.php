<?php

namespace App\Http\Controllers\V1\Inertia\Student\Sale;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Sale\FetchCartRepo;
use Inertia\Inertia;

class StudentCartController extends Controller
{

    public function index()
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/panel/PanelPage');
    }
}
