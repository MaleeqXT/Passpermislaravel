<?php

namespace App\Http\Controllers\V1\Inertia\System\Zone;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Params\Zone\AttachOrDetachZoneToEleveRequest;
use App\Http\Requests\V1\Admin\Params\Zone\Lieux\AttachOrDetachLieuToMoniteurRequest;
use App\Http\Requests\V1\Admin\Params\Zone\StoreZoneRequest;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Admin\Zone\AttachZoneToStudentRepo;
use App\Repository\V2\Admin\Zone\DestroyZoneRepo;
use App\Repository\V2\Admin\Zone\DetachZoneToStudentRepo;
use App\Repository\V2\Admin\Zone\EditZoneRepo;
use App\Repository\V2\Admin\Zone\FetchAllZoneRepo;
use App\Repository\V2\Admin\Zone\FetchZoneRepo;
use App\Repository\V2\Admin\Zone\StoreZoneRepo;
use App\Repository\V2\Monitor\Zone\AttachLieuToMonitorRepo;
use App\Repository\V2\Monitor\Zone\DetachLieuToMonitorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class  ZoneController extends Controller
{

    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {

        return Inertia::render('features/settings/locations/AreasPage', [
            'zones' => FetchAllZoneRepo::run(request()->all())
        ]);
    }

    // /**
    //  * @return Response
    //  */
    // public function create(): Response
    // {
    //     
    //     return Inertia::render('features/settings/locations/ZonePage');
    // }


    /**
     * @param StoreZoneRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreZoneRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create Zone
            $zone = StoreZoneRepo::run($request->validated());

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


    // /**
    //  * @param Zone $zone
    //  * @return Response
    //  */
    // public function edit(Zone $zone): Response
    // {
    //     
    //     return Inertia::render('features/settings/locations/ZonePage', [
    //         'zone' => FetchZoneRepo::run($zone, request()->all())
    //     ]);
    // }

    /**
     * @param StoreZoneRequest $request
     * @param Zone $zone
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(StoreZoneRequest $request, Zone $zone): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = EditZoneRepo::run($zone, $request->validated());

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
     * @param Zone $zone
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Zone $zone): RedirectResponse
    {
        DB::beginTransaction();
        try {

            // delete produit
            $status = DestroyZoneRepo::run($zone);
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


    /**
     * @param Student $student
     * @param AttachOrDetachZoneToEleveRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function attachZoneToEleve(Student $student, AttachOrDetachZoneToEleveRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = AttachZoneToStudentRepo::run($student, $request->validated());
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

    /**
     * @param Monitor $monitor
     * @param AttachOrDetachLieuToMoniteurRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function attachLieuToMoniteur(Monitor $monitor, AttachOrDetachLieuToMoniteurRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = AttachLieuToMonitorRepo::run($monitor, $request->validated());
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

    /**
     * @param Student $student
     * @param AttachOrDetachZoneToEleveRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function detachZoneToEleve(Student $student, AttachOrDetachZoneToEleveRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = DetachZoneToStudentRepo::run($student, $request->validated());
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

    /**
     * @param Monitor $monitor
     * @param AttachOrDetachLieuToMoniteurRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function detachLieuToMoniteur(Monitor $monitor, AttachOrDetachLieuToMoniteurRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = DetachLieuToMonitorRepo::run($monitor, $request->validated());
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
