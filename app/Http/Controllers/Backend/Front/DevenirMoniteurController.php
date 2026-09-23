<?php

namespace App\Http\Controllers\Backend\Front;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UserRequest;
use App\Models\User;
use App\Repository\User\StoreUser;
use App\Repository\V2\Monitor\User\StoreMonitorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DevenirMoniteurController extends Controller
{
    /**
     * @return Response
     */
    public function create(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/auth/RegisterMoniteurPage');
    }

    // @todo i create new moniteur store (reda check this function please)

    /**
     * @param StoreMonitorRepo $createMoniteur
     * @param StoreUser $createUser
     * @param UserRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreMonitorRepo $createMoniteur, StoreUser $createUser, UserRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create User
            $password = Str::password(8, true, true, false, false);
            $user = $createUser::run($request->validated() + ["password" => $password, "media" => "", 'role' => UserRolesEnum::MONITOR->value, "status" => SituationStatusEnum::INPROGRESS->value]);

            // create Moniteur
            $createMoniteur::run($user?->id, $request->only(["dernier_experience", "details_experience", 'zone_souhaitee']));

            session()->flash('success', 'Votre compte a été créé avec succès');
            DB::commit();
            // return
            // // Inertia::setRootView('espace-monitor');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la création du User');
            throw $e;
        }
    }

    public function setupPassword(User $user): Response
    {
        // Inertia::setRootView('front');
        return Inertia::render(
            'features/auth/SetupPasswordPage',
            [
                'email' => $user->email,
                'token' => request()->get('token'),

            ]
        );
    }
}
