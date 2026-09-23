<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MonitorCancellationController extends Controller
{

    /**
     *   /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/monitoring/cancellations/CancellationsPage');
    }
}
