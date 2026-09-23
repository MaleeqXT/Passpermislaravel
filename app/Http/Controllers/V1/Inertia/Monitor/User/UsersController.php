<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdatePasswordRequest;
use App\Http\Requests\V1\Admin\User\UserRequest;
use App\Http\Requests\V1\Monitor\User\StoreOrUpdateMonitorRequest;
use App\Http\Requests\V1\Monitor\User\UpdateMonitorRequest;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\User;
use App\Repository\User\EditUser;
use App\Repository\User\EditUserPasswordAction;
use App\Repository\User\StoreUser;
use App\Repository\V2\Monitor\User\EditMonitorRepo;
use App\Repository\V2\Monitor\User\FetchMonitorRepo;
use App\Repository\V2\Monitor\User\StoreMonitorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{

    /**
     * 
     */
    public function index(Request $request): JsonResponse
    {
        $targetId = $request->query('monitor_id');
        $monitor = $targetId
            ? Monitor::query()
                ->whereKey($targetId)
                ->orWhere('user_id', $targetId)
                ->with(['user', 'details', 'lieux'])
                ->first()
            : auth()->user()?->monitor?->load(['user', 'details', 'lieux']);

        if (!$monitor || !$monitor->user) {
            return response()->json([
                'message' => 'Profil moniteur introuvable.',
            ], 404);
        }

        $user = $monitor->user;
        $user->setRelation('monitor', $monitor);

        return response()->json(['data' => $user]);
    }


    // /**
    //  * @return Response
    //  */
    // public function passwordChange(): Response
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/settings/account/PasswordChangePage', [
    //         'user' => FetchMonitorRepo::run(request()->all()),
    //         "hideBottomBar" => true,
    //     ]);
    // }

    /**
     * @return Response
     */
    public function create(): Response
    {
        // Inertia::setRootView('front');
        return Inertia::render('features/auth/RegisterMoniteurPage');
    }


    /**

     */
public function store(UserRequest $request, StoreOrUpdateMonitorRequest $monitorRequest)
{

    DB::beginTransaction();

    try {
        // Create User
        $user = StoreUser::run($request->validated());

        // Create Monitor
        $monitor = StoreMonitorRepo::run($user->id, $monitorRequest->validated());

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Monitor created successfully.',
            'data' => [
                'user' => $user,
                'monitor' => $monitor,
            ],
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(), // Production mein is line ko remove kar dena.
        ], 500);
    }
}


    // /**
    //  * @param User $user
    //  * @return Response
    //  */
    // public function edit(User $user): Response
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/students/StudentsPage', [
    //         'user' => FetchMonitorRepo::run(request()->all(), $user->id)
    //     ]);
    // }

    /**
     */
//         public function update(Monitor $monitor, UpdateMonitorRequest $request)
// {
//     DB::beginTransaction();

//     try {
//          $userData = $request->validated();

//                  if ($request->hasFile('media')) {

//                         // optional: old file delete
//                         if ($monitor->user->media) {
//                             Storage::disk('public')->delete($monitor->user->media);
//                         }

//                         $userData['media'] = $request->file('media')->store('media', 'public');
//                     }

//         EditUser::run($monitor->user_id, $userData);
//         // $monitor->update($request->status);
//         $monitor->update([
//                 'status' => $request->status
//             ]);
//         EditMonitorRepo::run(
//             $monitor,
//             $request->only(
            
//                 'experience',
//                 'dernier_experience',
//                 'details_experience',
//                 'is_auto',
//                 'is_manual',
//                 'departement',
//                 'numero_autorisation',
//                 'tarif_car',
//                 'tarif_enseignement'
//             )
//         );

//         SyncLieuxToMonitorRepo::run(
//             $monitor,
//             $request->only('lieux')
//         );

//         StoreOrEditInstructorAccountRepo::run(
//             $monitor,
//             $request->only('iban', 'bic')
//         );

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'Le Moniteur est bien Modifier',
//             'data' => $monitor->fresh([
//                 'details',
//                 'lieux',
//                 'account',
//                 'user'
//             ])
//         ], 200);

//     } catch (Exception $e) {

//         DB::rollBack();

//         return response()->json([
//             'success' => false,
//             'message' => 'Une erreur est survenue lors de la modification du User',
//             'error'   => $e->getMessage(),
//         ], 500);
//     }
// }
    public function update(Monitor $monitor, UpdateMonitorRequest $monitorRequest)
    {
    
    DB::beginTransaction();

    try {
        $userData = $monitorRequest->validated();

                 if ($monitorRequest->hasFile('media')) {

                        // optional: old file delete
                        if ($monitor->user->media) {
                            Storage::disk('public')->delete($monitor->user->media);
                        }

                        $userData['media'] = $monitorRequest->file('media')->store('media', 'public');
                    }

   
        EditUser::run($monitor->user_id, $userData);

        EditMonitorRepo::run(
            $monitor,
            $monitorRequest->only(
                'experience',
                'dernier_experience',
                'details_experience',
                'is_auto',
                'is_manual'
            )
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Monitor updated successfully.',
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(), // Production mein remove kar dena
        ], 500);
    }
}
    


    // /**
    //  * @param Monitor $monitor
    //  * @param UpdatePasswordRequest $request
    //  * @return RedirectResponse
    //  * @throws Exception
    //  */
    // public function updatePassword(Monitor $monitor, UpdatePasswordRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {

    //         EditUserPasswordAction::run($monitor->user, $request->only('password'));
    //         DB::commit();
    //         session()->flash('success', flashMessage());
    //         // return
    //         // Inertia::setRootView('espace-monitor');
    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }
}
