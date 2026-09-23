<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\StoreOrUpdateScheduleRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Repository\V2\Monitor\User\FetchAllMonitorRepo;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ReservationAdminController extends Controller
{

    public function __construct(public ReservationInterface $service) {}


    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response

    {

        return Inertia::render('features/general/reservations/ReservationsPage');
    }

   public function unrestrictedIndex(): Response
    {
        return Inertia::render('features/general/reservations_unrestricted/ReservationsPage');
    }

    /**
     * @param StoreOrUpdateScheduleRequest $request
     * @return RedirectResponse
     * @throws Exception
     */

    public function store(StoreOrUpdateScheduleRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create Planning
            $this->service->create($request->validated());

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
     * @param Reservation $reservation
     * @param StoreOrUpdateScheduleRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Reservation $reservation, StoreOrUpdateScheduleRequest $request): RedirectResponse

    {
        DB::beginTransaction();
        try {
            $this->service->update($reservation, $request->validated());

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
     * @param Reservation $reservation
     * @param ReservationInterface $service
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Reservation $reservation, ReservationInterface $service): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // delete user
            $status = $service->delete($reservation);
            DB::commit();

            session()->flash('success', flashMessage());
            // return

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
