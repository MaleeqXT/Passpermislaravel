<?php

namespace App\Http\Controllers\V1\Inertia\Student\Evaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Evaluation\UpdateStudentRequest;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Evaluation\FetchDocumentEvaluationRepo;
use App\Repository\V2\Monitor\Evaluation\StoreDocumentEvaluationRepo;
use App\Repository\V2\Student\User\EditStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentEvaluationController extends Controller
{


    /**
     * @param EditStudentRepo $repo
     * @param UpdateStudentRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function choseDissection(EditStudentRepo $repo, UpdateStudentRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $repo->run(auth()->user()->student, $request->validated());
            DB::commit();
            session()->flash('success', flashMessage());

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('features/...', [
            'evaluations' => FetchDocumentEvaluationRepo::run(),
        ]);
    }



    /**
     * @param DocumentEvaluation $documentEvaluation
     * @return \Illuminate\Http\Response
     */
    public function evaluationsPdf(DocumentEvaluation $documentEvaluation)
    {
        $documentEvaluation->load('student.user', 'monitor.user:id,name');
        $pdf = PDF::loadView('pdf.normal.evaluations', $documentEvaluation->toArray())
            ->setPaper('a4');
        // $pdf = Pdf::loadView('pdf.normal.evaluations', ['evaluation' => FetchDocumentEvaluationRepo::run()])->setPaper('A5');
        return $pdf->stream('fich-evaluation.pdf');
    }

    /**
     * @param Reservation $reservation
     * @return \Illuminate\Http\Response
     */
    public function contractPdf(Reservation $reservation)
    {
        $reservation->load([
            'monitor.user:id,name',
            'training.student.user',
            'training.offer',
            'reviewMonitor',
            'evaluation',
        ]);
        // dd($reservation->toArray());
        $pdf = PDF::loadView('pdf.normal.contract-formation', $reservation->toArray());
        // $pdf = Pdf::loadView('pdf.normal.evaluations', ['evaluation' => FetchDocumentEvaluationRepo::run()])->setPaper('A5');
        return $pdf->stream('contract-de-formation.pdf');
    }
}
