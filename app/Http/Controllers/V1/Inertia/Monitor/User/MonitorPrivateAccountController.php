<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\StoreOrUpdateAccountMonitorRequest;
use App\Repository\V2\Monitor\Instructor\Information\FetchInstructorAccountRepo;
use App\Repository\V2\Monitor\Instructor\Information\StoreOrEditInstructorAccountRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonitorPrivateAccountController extends Controller
{


    /**
     * @return Response
     */
    public function index()
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/account/AccountPage', [
            'account' => FetchInstructorAccountRepo::run(request()->all()),
            "hideBottomBar" => true,
        ]);
    }


    /**
     * @param StoreOrUpdateAccountMonitorRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeOrUpdate(StoreOrUpdateAccountMonitorRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            StoreOrEditInstructorAccountRepo::run(auth()->user()->monitor, $request->validated());
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
