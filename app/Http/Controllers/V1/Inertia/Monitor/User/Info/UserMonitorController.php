<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\User\Info;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\StoreOrUpdateMonitorRequest;
use App\Http\Requests\V1\Monitor\User\UpdateMonitorRequest;
use App\Models\Monitor as ModelsMonitor;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\User;
use App\Repository\User\DestroyUser;
use App\Repository\User\EditUser;
use App\Repository\User\StoreUser;
use App\Repository\V2\Monitor\Billing\FetchAllInvoiceRepo;
use App\Repository\V2\Monitor\Instructor\Information\StoreOrEditInstructorAccountRepo;
use App\Repository\V2\Monitor\User\EditMonitorRepo;
use App\Repository\V2\Monitor\User\FetchAllMonitorRepo;
use App\Repository\V2\Monitor\User\FetchMonitorRepo;
use App\Repository\V2\Monitor\User\StoreMonitorRepo;
use App\Repository\V2\Monitor\Zone\SyncLieuxToMonitorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserMonitorController extends Controller
{
    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    // public function index(): Response
    // {

    //     return Inertia::render('features/roles/monitors/MonitorsPage', [
    //         'monitors' => FetchAllMonitorRepo::run(request()->all())
    //     ]);
    // }

    public function index()
        {
            $user = auth()->user();
            $zoneIds = array_values(array_filter((array) request()->input('zone_id')));
            $zoneIds = $zoneIds ?: array_values(array_filter((array) $user->zone_id));

            // A monitor belongs to a user. Its visible zone is therefore the
            // zone saved in users.zone_id, not the optional monitor locations.
            $monitorsForSelectedZone = Monitor::where(function ($query) use ($zoneIds) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->whereIn('zone_id', $zoneIds))
                    ->orWhere(function ($legacyQuery) use ($zoneIds) {
                        $legacyQuery->whereHas('user', fn ($userQuery) => $userQuery->whereNull('zone_id'))
                            ->whereHas('lieux', fn ($locationQuery) => $locationQuery->whereIn('zone_id', $zoneIds));
                    });
            });

            $activeCount = (clone $monitorsForSelectedZone)->where('status', 1)->count();
            $archiveCount = (clone $monitorsForSelectedZone)->where('status', 2)->count();
            $inhold = (clone $monitorsForSelectedZone)->where('status', 3)->count();
            $countAll = (clone $monitorsForSelectedZone)->count();
            // return response()->json("hellooo hello");
            return response()->json([
                'success' => true,
                'message' => 'Monitors fetched successfully',
                'data' => FetchAllMonitorRepo::run(request()->all(), $zoneIds),
                // 'active_count'=>Monitor::where('status',1)->count(),
                // 'inactive_count'=>Monitor::where('status',2)->count(),
                // 'inhold_count'=>Monitor::where('status',3)->count(),
                'active_count' => $activeCount,

                'inactive_count' => $archiveCount,

                'inhold_count' =>$inhold,
                'all_count' => $countAll,
            ], 200);


    //           return response()->json([
    //     'success' => true,
    //      'active_count'  => Secretary::where('status', 1)->count(),  // ✅
    //     'archive_count' => Secretary::where('status', 2)->count(),  // ✅

    //     'data' => $action::run(request()->all()),
    // ]);
        }


    /**
     * @return Response
     */
    public function create(): Response
    {

        return Inertia::render('features/roles/monitors/MonitorCreatePage');
    }

    /**
     * @param Monitor $monitor
     * @return Response
     */
    public function invoices(Monitor $monitor): Response
    {

        return Inertia::render('features/roles/monitors/view/MonitorInvoicesPage', [
            'invoices' =>  FetchAllInvoiceRepo::run(array_merge(request()->all(), ['monitor_id' => $monitor->id])),
            'monitor' => $monitor->load('user')
        ]);
    }


    /**
     * @param StoreUser $createUser
     * @param StoreOrUpdateMonitorRequest $request
     * @return RedirectResponse
     * @throws ValidationException|Exception
     */

public function store(StoreOrUpdateMonitorRequest $request)
{
    DB::beginTransaction();

    try {

        $mediaPath = null;
         $zone_id = auth()->user()->zone_id;
        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store('media', 'public');
        }

        $user = StoreUser::run(array_merge($request->validated(), [
            'role' => 'monitor',
            'media' => $mediaPath,
             'zone_id'=>$zone_id,
        ]));

        $user->assignRole('monitor');

        $monitor = StoreMonitorRepo::run($user->id, $request->validated());

        StoreOrEditInstructorAccountRepo::run($monitor, $request->validated());

        SyncLieuxToMonitorRepo::run($monitor, $request->only('lieux'));

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Moniteur successfully created',
            'data' => [
                'user' => $user,
                'monitor' => $monitor,
            ]
        ], 201);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error occurred while creating user',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    /**
     * @param Monitor $monitor
     * @return Response
     */
    // public function edit(Monitor $monitor): Response
    // {

    //     return Inertia::render('features/roles/monitors/view/MonitorEditPage', [
    //         'monitor' =>  $monitor->load(['details', 'lieux', 'account', 'user']) //FetchMonitorRepo::run(request()->all(), $monitor->user_id)
    //     ]);
    // }

    public function edit(Monitor $monitor)
        {
            return response()->json([
                'monitor' => $monitor->load([
                    'details',
                    'lieux',
                    'account',
                    'user'
                ])
            ]);
}

    /** Move a monitor between Active (1), Inactif (2) and En attente (3). */
    public function updateStatus(Request $request, Monitor $monitor)
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $monitor->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Statut du moniteur mis à jour.',
            'data' => $monitor->fresh(),
        ]);
    }

    /**
     * @param Monitor $monitor
     * @param UpdateMonitorRequest $request
     * @return RedirectResponse
     * @throws Exception
     */

    public function update(Monitor $monitor, UpdateMonitorRequest $request)
{
    DB::beginTransaction();

    try {
         $userData = $request->validated();

                 if ($request->hasFile('media')) {

                        // optional: old file delete
                        if ($monitor->user->media) {
                            Storage::disk('public')->delete($monitor->user->media);
                        }

                        $userData['media'] = $request->file('media')->store('media', 'public');
                    }

        EditUser::run($monitor->user_id, $userData);
        // $monitor->update($request->status);
        $monitor->update([
                'status' => $request->status
            ]);
        EditMonitorRepo::run(
            $monitor,
            $request->only(
            
                'experience',
                'dernier_experience',
                'details_experience',
                'is_auto',
                'is_manual',
                'departement',
                'numero_autorisation',
                'tarif_car',
                'tarif_enseignement'
            )
        );

        SyncLieuxToMonitorRepo::run(
            $monitor,
            $request->only('lieux')
        );

        StoreOrEditInstructorAccountRepo::run(
            $monitor,
            $request->only('iban', 'bic')
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Le Moniteur est bien Modifier',
            'data' => $monitor->fresh([
                'details',
                'lieux',
                'account',
                'user'
            ])
        ], 200);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la modification du User',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    // public function update(Monitor $monitor, UpdateMonitorRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction(); 
    //     try {
    //         EditUser::run($monitor->user_id, $request->validated());
    //         EditMonitorRepo::run($monitor, $request->only('experience', 'dernier_experience', 'details_experience', 'is_auto', 'is_manual', 'departement', 'numero_autorisation', 'tarif_car', 'tarif_enseignement'));
            

    //         SyncLieuxToMonitorRepo::run($monitor, $request->only('lieux'));
    //         StoreOrEditInstructorAccountRepo::run($monitor, $request->only('iban','bic'));


    //         DB::commit();
    //         session()->flash('success', 'Le Moniteur est bien Modifier');

    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', 'Une erreur est survenue lors de la modification du User');
    //         throw $e;
    //     }
    // }

    /**
     * @param User $user
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(User $user): RedirectResponse
    {
        DB::beginTransaction();
        try {

            // delete user
            $status = DestroyUser::run($user);
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
