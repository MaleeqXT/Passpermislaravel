<?php

namespace App\Http\Controllers\V1\Inertia\Student\Competence;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Competency\UpdateOrCreateCompetencyRatingRequest;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\FetchMainCompetencyRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\StoreOrEditCompetencyRatingRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompetenceMonitorController extends Controller
{

    /**
     *   /**
     * @param Student $student
     * @return Response
     */
    public function index(Student $student): Response
    {

        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/evaluations/competences/CompetencesPage', [
            'student' => $student->load('user'),
            'hideBottomBar' => true,
            'competencies' => FetchMainCompetencyRepo::run($student, request()->all()),
        ]);
    }


    /**
     * @param Competency $competency
     * @param UpdateOrCreateCompetencyRatingRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeOrUpdate(Competency $competency, UpdateOrCreateCompetencyRatingRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create Or Update Feedback
            StoreOrEditCompetencyRatingRepo::run($competency, $request->validated());


            DB::commit();
            session()->flash('success', flashMessage());

            // return
            // Inertia::setRootView('espace-monitor');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
