<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Evaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Evaluation\StoreDocumentEstimatedRequest;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Evaluation\StoreDocumentEvaluationRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DocumentEvaluationController extends Controller
{



    /**
     * @param StoreDocumentEstimatedRequest $request
     * @param Student $student
     * @param StoreDocumentEvaluationRepo $action
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreDocumentEstimatedRequest $request, Student $student, StoreDocumentEvaluationRepo $action): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $f = $action->run($student, $request->validated());
            DB::commit();

            session()->flash('success', flashMessage());

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
