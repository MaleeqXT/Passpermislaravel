<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Schedule\Training\UpdateTrainingProposalRequest;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchAllTrainingProposalByAdminRepo;
use App\Services\Student\Training\Proposal\ProposalInterface;
use Exception;
use Illuminate\Http\JsonResponse;
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

 public function update(TrainingProposal $trainingProposal, UpdateTrainingProposalRequest $request): JsonResponse|RedirectResponse

 {
        // Remove the dd() - it was preventing the update
        // dd($request->all());

        DB::beginTransaction();
        try {
            $proposal = $this->service->updateAndCreatePropositionSession($trainingProposal, $request->validated());
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['data' => $proposal]);
            }

            return redirect()->back()->with('success', 'Proposition mise à jour avec succès');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage() ?: 'Impossible de mettre à jour la proposition.'], 422);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

}
