<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\Info\Car\StoreOrUpdateCarRequest;
use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use App\Repository\V2\Monitor\Instructor\Car\EditCarRepo;
use App\Repository\V2\Monitor\Instructor\Car\FetchAllCarsRepo;
use App\Repository\V2\Monitor\Instructor\Car\StoreCarRepo;
use App\Repository\V2\Monitor\Instructor\Cart\Assurance\StoreOrEditAssuranceRepo;
use App\Repository\V2\Monitor\Instructor\Cart\GrayCart\StoreOrEditGrayCarCartRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CarMoniteurController extends Controller
{


    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/vehicle/VehiclePage', [
            'cars' => FetchAllCarsRepo::run(request()->all()),
        ]);
    }


    /**
     * @return Response
     */
    public function create(): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/vehicle/VehiclePage');
    }

    /**
     * @param Car $car
     * @return Response
     */
    public function edit(Car $car): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/vehicle/VehiclePage', [
            'car' => $car->load('carteGris', 'assurance')
        ]);
    }


    /**
     * @param StoreOrUpdateCarRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreOrUpdateCarRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $car = StoreCarRepo::run(auth()->user()->monitor, $request->except('media_carte', 'media_assurance'));
            StoreOrEditGrayCarCartRepo::run($car, $request->only('media_carte'));
            StoreOrEditAssuranceRepo::run($car, $request->only('media_assurance'));
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }


    /**
     * @param Car $car
     * @param StoreOrUpdateCarRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Car $car,  StoreOrUpdateCarRequest $request)
    {
        DB::beginTransaction();
        try {
            EditCarRepo::run($car, $request->except('media_carte', 'media_assurance'));

            StoreOrEditGrayCarCartRepo::run($car, $request->only('media_carte'));
            StoreOrEditAssuranceRepo::run($car, $request->only('media_assurance'));
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param Car $car
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Car $car)
    {
        DB::beginTransaction();
        try {
            $car->delete();
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
