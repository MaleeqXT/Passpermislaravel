<?php

namespace App\Http\Controllers\V1\Inertia\System\User;

use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdateUserRequest;
use App\Http\Requests\V1\Admin\User\UserRequest;
use App\Models\User;
use App\Repository\User\DestroyUser;
use App\Repository\User\EditUser;
use App\Repository\User\StoreUser;
use App\Repository\V2\Admin\User\FetchAdminRepo;
use App\Repository\V2\Admin\User\FetchAllRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    /**
     * return JsonResponse
     * throws ContainerExceptionInterface
     * throws NotFoundExceptionInterface
     */
        public function index()
            { 
            $users = FetchAllRepo::run(request()->all());

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        public function changeStatus(User $user)
{
    $user->status = $user->status == 1 ? 2 : 1;
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Statut modifié avec succès.',
        'data' => $user
    ]);
}

    /**
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('features/roles/admins/AdminCreatePage');
    }


   

    public function store(StoreUser $createUser, UserRequest $request)
            {
                DB::beginTransaction();
                try {
                    $user = $createUser::run(array_merge($request->validated(), ['role' => UserRolesEnum::ADMIN->value]));
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Administrateur créé avec succès.',
                        'data' => $user
                    ]);
                } catch (Exception $e) {
                    DB::rollback();

                    return response()->json([
                        'success' => false,
                        'message' => 'Une erreur est survenue lors de la création.'
                    ], 500);
                }
            }

    // public function store(StoreUser $createUser, UserRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $user = $createUser::run(array_merge($request->validated(), ['role' => UserRolesEnum::ADMIN->value]));
    //         DB::commit();
    //         session()->flash('success', flashMessage());

    //         // return

    //         return redirect()->route('admin.users.admins.edit', ['user' => $user->id, 'created' => true]);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }

    public function edit(User $user): Response
    {

        return Inertia::render('features/roles/admins/AdminEditPage', [
            'user' => FetchAdminRepo::run($user->id, request()->all())
        ]);
    }

    /**
   
     */
   public function update(Request $request, User $user)
{
    DB::beginTransaction();
    try {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email',
            'status'     => 'nullable|integer',
            'phone'      => 'nullable|string|max:255',
            'media'      => 'nullable|string|max:1024',
            'role'       => 'nullable|string',
            'password'   => 'nullable|string|min:4|max:255',
        ]);

        $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!empty($validated['media'])) {
            $validated['profile_photo_path'] = $validated['media'];
        }

        $user->update($validated);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Admin updated successfully!',
            'data' => $user
        ]);

    } catch (Exception $e) {
        DB::rollback();
        Log::error('User update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Update failed: ' . $e->getMessage()
        ], 500);
    }
}


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
