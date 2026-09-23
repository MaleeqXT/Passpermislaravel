<?php

namespace App\Http\Controllers\Backend\Admin\Exams;

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Examen\UpdateExamenEleveRequest;
use App\Models\Roles\Student\Exam\StudentExam;
use App\Repository\V2\Monitor\Schedule\Reservation\Examen\EditExamenStudentRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Examen\FetchListExamenStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamListController extends Controller
{

    public function index(): Response
    {

        return Inertia::render('features/general/examinations/ExaminationsPage', [
            'exams' => FetchListExamenStudentRepo::run(request()->all())
        ]);
    }

    public function edit(StudentExam $examenEleve): Response
    {

        return Inertia::render('features/general/examinations/ExaminationEditPage', [
            'exam' => $examenEleve->load('user'),
        ]);
    }


    /**
     * @param StudentExam $examenEleve
     * @param EditExamenStudentRepo $action
     * @param UpdateExamenEleveRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(StudentExam $examenEleve, EditExamenStudentRepo $action, UpdateExamenEleveRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {

            $action::run($examenEleve, $request->validated());
            DB::commit();
            session()->flash('success', 'Examen mis à jour avec succès');
            // return
            return redirect()->route('admin.exams.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Erreur lors de la mise à jour de l\'examen');
            throw $e;
        }
    }
}
