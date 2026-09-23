<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\UpdateTrainingProposalRequest;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchAllTrainingProposalByAdminRepo;
use App\Services\Student\Training\Proposal\ProposalInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminProposalTrainingController extends Controller
{

    public function __construct(public ProposalInterface $service) {}

    public function index()
    {

        return Inertia::render('features/general/proposals/ProposalsPage', [
            // 'data' => FetchAllTrainingProposalByAdminRepo::run(request()->all()),
        ]);
    }

    public function proposals()
    {
        return response()->json([
            'data' =>   FetchAllTrainingProposalByAdminRepo::run(request()->all())
        ]);
    }

    /**
     * @param TrainingProposal $trainingProposal
     * @param UpdateTrainingProposalRequest $request
     * @return RedirectResponse
     * @throws Exception
     */

 public function update(TrainingProposal $trainingProposal, UpdateTrainingProposalRequest $request): RedirectResponse

 {
        // Remove the dd() - it was preventing the update
        // dd($request->all());

        DB::beginTransaction();
        try {
            $this->service->updateAndCreatePropositionSession($trainingProposal, $request->validated());
            DB::commit();

            return redirect()->back()->with('success', 'Proposition mise à jour avec succès');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

}
