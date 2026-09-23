<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\Schedule\StoreOrUpdateScheduleRequest;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\CountByMonthAllReservationByMonitorRepo;
use App\Repository\V2\Student\User\FetchStudentRepo;
use App\Repository\V2\Student\User\GetStudentRepo;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReservationMonitorController extends Controller
{


    public function __construct(public ReservationInterface $service) {}

    /**
     * @param CountByMonthAllReservationByMonitorRepo $repo
     * @return Response
     */
    public function index(CountByMonthAllReservationByMonitorRepo $repo): Response
    {

        // $data = array_merge();
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/reservations/ReservationsPage', [
            'student' => request()->get('student_id') ? GetStudentRepo::run(['student_id' => request()->get('student_id')]) : null,
            // 'events' => $repo->run(['monitor_id' => [auth()->user()->monitor->id]] + request()->all())
        ]);
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
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Reservation $reservation): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->service->delete($reservation);

            DB::commit();

            session()->flash('success', flashMessage());
            // return
            // Inertia::setRootView('espace-monitor');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
