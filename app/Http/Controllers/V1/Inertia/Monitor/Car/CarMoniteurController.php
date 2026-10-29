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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Roles\Monitor\User\Monitor;

class CarMoniteurController extends Controller
{

    /** JSON endpoints used by the React monitor dashboard. */
    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json([
            'data' => FetchAllCarsRepo::run($request->all()),
        ]);
    }

    public function apiStore(StoreOrUpdateCarRequest $request): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);
        if (! $monitor) {
            return response()->json(['message' => 'Profil moniteur introuvable.'], 422);
        }

        DB::transaction(function () use ($request, $monitor, &$car) {
            $car = StoreCarRepo::run($monitor, $request->except(['media_carte', 'media_assurance', 'monitor_id']));
            StoreOrEditGrayCarCartRepo::run($car, ['car_id' => $car->id] + $request->only('media_carte'));
            StoreOrEditAssuranceRepo::run($car, ['car_id' => $car->id] + $request->only('media_assurance'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Véhicule ajouté avec succès.',
            'data' => $car->fresh()->load('grayCarCart.media', 'assurance.media'),
        ], 201);
    }

    public function apiUpdate(StoreOrUpdateCarRequest $request, Car $car): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);
        if (! $monitor || $car->monitor_id !== $monitor->id) {
            return response()->json(['message' => 'Véhicule introuvable.'], 404);
        }

        DB::transaction(function () use ($request, $car) {
            EditCarRepo::run($car, $request->except(['media_carte', 'media_assurance', 'monitor_id']));
            StoreOrEditGrayCarCartRepo::run($car, ['car_id' => $car->id] + $request->only('media_carte'));
            StoreOrEditAssuranceRepo::run($car, ['car_id' => $car->id] + $request->only('media_assurance'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Véhicule modifié avec succès.',
            'data' => $car->fresh()->load('grayCarCart.media', 'assurance.media'),
        ]);
    }

    public function apiDestroy(Request $request, Car $car): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);
        if (! $monitor || $car->monitor_id !== $monitor->id) {
            return response()->json(['message' => 'Véhicule introuvable.'], 404);
        }

        $car->delete();

        return response()->json(['success' => true, 'message' => 'Véhicule supprimé avec succès.']);
    }

    private function resolveMonitor(Request $request): ?Monitor
    {
        if ($request->filled('monitor_id')) {
            return Monitor::query()->find($request->input('monitor_id'));
        }

        return $request->user()?->monitor;
    }


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
