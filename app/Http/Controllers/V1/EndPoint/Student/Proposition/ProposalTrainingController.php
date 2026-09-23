<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Proposition;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\TrainingProposal\StoreManyTrainingProposalRequest;
use App\Http\Requests\V1\Student\Schedule\Training\StoreOrUpdateTrainingProposalRequest;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\CountTrainingProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchAllTrainingProposalRepo;
use App\Services\Student\Training\Proposal\ProposalInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalTrainingController extends Controller
{
    public function __construct(public ProposalInterface $service) {}


    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => FetchAllTrainingProposalRepo::run(request()->all())]);
    }


    /**
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        return response()->json(['data' => CountTrainingProposalRepo::run(request()->all())]);
    }

    /**
     * @throws ValidationException|Exception
     */
    public function update(TrainingProposal $trainingProposal, ProposalInterface $training, StoreOrUpdateTrainingProposalRequest $request): Builder|array|Collection|Model
    {
        DB::beginTransaction();
        try {
            $training->updateAndCreatePropositionSession($trainingProposal, $request->validated());
            DB::commit();
            session()->flash('success', 'La Réservation est bien Modifier');

            return $trainingProposal;
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification du Reservation');
            throw $e;
        }
    }

    /**
     * @param StoreManyTrainingProposalRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeMany(StoreManyTrainingProposalRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->service->createMany($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Proposition de seance a envoyé avec succès',
                'status' => 201
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }


    /**
     * @param TrainingProposal $trainingProposal
     * @return RedirectResponse
     * @throws ValidationException
     * @throws Exception
     */
    public function delete(TrainingProposal $trainingProposal)
    {
        DB::beginTransaction();
        try {
            $this->service->delete($trainingProposal);
            DB::commit();
            return response()->json([
                'message' => 'Proposition de seance a été supprimé avec succès',
                'status' => 201
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
