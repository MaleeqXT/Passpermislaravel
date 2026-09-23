<?php

namespace App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\TrainingProposal\StoreManyTrainingProposalRequest;
use App\Http\Requests\V1\Monitor\Schedule\TrainingProposal\StoreTrainingProposalRequest;
use App\Http\Requests\V1\Monitor\Schedule\TrainingProposal\UpdateTrainingProposalRequest;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchAllTrainingProposalRepo;
use App\Services\Student\Training\Proposal\ProposalInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MonitorProposalTrainingController extends Controller
{


    public function __construct(public ProposalInterface $service) {}

    public function index()
    {
        return response()->json([
            'proposals' => FetchAllTrainingProposalRepo::run(request()->all()),
        ]);
    }

    // public function index()
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/monitoring/proposals/ProposalsPage', [
    //         'proposals' => FetchAllTrainingProposalRepo::run(request()->all()),
    //     ]);
    // }



    /**
     * @param StoreManyTrainingProposalRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeMany(StoreManyTrainingProposalRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->service->createMany($request->validated());
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param StoreTrainingProposalRequest $request
     * @return RedirectResponse
     * @throws ValidationException|Exception
     */
    public function store(StoreTrainingProposalRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->service->create($request->validated());
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }


    /**
     * @param TrainingProposal $trainingProposal
     * @param UpdateTrainingProposalRequest $request
     * @return RedirectResponse
     * @throws ValidationException
     * @throws Exception
     */
    public function update(TrainingProposal $trainingProposal, UpdateTrainingProposalRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->service->update($trainingProposal, $request->validated());
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
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
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
