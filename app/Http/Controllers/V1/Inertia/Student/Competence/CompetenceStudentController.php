<?php

namespace App\Http\Controllers\V1\Inertia\Student\Competence;

use App\Http\Controllers\Controller;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\FetchMainCompetencyRepo;
use Inertia\Inertia;
use Inertia\Response;

class CompetenceStudentController extends Controller
{

    /**
     *   /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/competences/CompetencesPage', [
            'competencies' => FetchMainCompetencyRepo::run(auth()->user()?->student, request()->all()),
        ]);
    }
}
