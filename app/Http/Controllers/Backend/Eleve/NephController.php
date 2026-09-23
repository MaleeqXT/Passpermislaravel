<?php

namespace App\Http\Controllers\Backend\Eleve;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\User\Params\UpdateNephRequest;
use App\Repository\V2\Student\User\EditStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;


class NephController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/settings/neph/NephPage', [
            "hideBottomBar" => true,
        ]);
    }

    /**
     * @param UpdateNephRequest $request
     * @param EditStudentRepo $action
     * @return Response
     */
    public function update(UpdateNephRequest $request, EditStudentRepo $action): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $student = auth()->user()->student;
            // update eleve
            $action::run($student, $request->validated());
            DB::commit();
            session()->flash('success', 'Votre coordonnées ont été mises à jour avec succès');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification d\'student');
            throw $e;
        }
    }
}
