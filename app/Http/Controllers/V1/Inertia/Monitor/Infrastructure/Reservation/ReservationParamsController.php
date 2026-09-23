<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\StoreOrUpdateParamsScheduleRequest;
use App\Services\Student\Training\Reservation\params\ReservationPramsInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReservationParamsController extends Controller
{


    public function __construct(public ReservationPramsInterface $service) {}

    /**
     * @return Response
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/...',);
    }


    /**
     * @throws Exception
     */
    public function storeOrUpdate(StoreOrUpdateParamsScheduleRequest $request, ReservationPramsInterface $service): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create Planning
            $service->createOrUpdate($request->validated());

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
