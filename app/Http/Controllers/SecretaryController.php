<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Secretary;
use App\Repository\User\StoreUser;
use App\Repository\V2\Secretary\StoreSecretaryRepo;
use App\Repository\V2\Secretary\FetchAllSecretaryRepo;
use App\Http\Requests\V1\Secretary\StoreSecretaryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class SecretaryController extends Controller
{
    /**
     * Secretary dashboard list page
     */

    public function index(FetchAllSecretaryRepo $action)
{
    $user = auth()->user();
    $secretariesForSelectedZone = Secretary::query();

    $activeCount = (clone $secretariesForSelectedZone)->where('status', 1)->count();
    $archiveCount = (clone $secretariesForSelectedZone)->where('status', 2)->count();

  

    // If the user is a secretary, only show their own record
    if ($user->hasRole('secretary')) {
        $secretaries = Secretary::with('user')
            ->where('user_id', $user->id)
            ->paginate();

        return response()->json([
            'success' => true,
            'active_count' => $activeCount,
            'archive_count' => $archiveCount,
            'data' => $secretaries,
        ]);
    }

    // If admin, show all secretaries
    return response()->json([
        'success' => true,
        'active_count' => $activeCount,
        'archive_count' => $archiveCount,
        'data' => $action::run(request()->all()),
    ]);
}
    // public function index(FetchAllSecretaryRepo $action): Response
    // {
    //     $user = auth()->user();

    //     // ✅ If the user is a secretary, only show their own record
    //     if ($user->hasRole('secretary')) {
    //         $secretary = Secretary::where('user_id', $user->id)->first();

    //         return Inertia::render('features/general/dashboard/SecretaryIndex', [
    //             'secretaries' => $secretary ? [$secretary->load('user')] : [],
    //         ]);
    //     }

    //     // ✅ If admin, show all secretaries
    //     return Inertia::render('features/general/dashboard/SecretaryIndex', [
    //         'secretaries' => $action::run(request()->all()),
    //     ]);
    // }

    /**
     * Create secretary page
     */
    public function create(): Response
    {
        // ✅ Only admin should be able to access create
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('features/general/dashboard/Secretary', [
            'ahmar' => 'Hi! 20 Ghanta (from Secretary)',
        ]);
    }


    public function store(
    StoreUser $createUser,
    StoreSecretaryRepo $createSecretary,
    StoreSecretaryRequest $request
    // Request $request
){

    // return $request->all();
//     return [
//     'all' => $request->all(),
//     'file' => $request->file('media'), // file object
//     'hasFile' => $request->hasFile('media'), // true/false
// ];


    // dd($request->json()->all());
    DB::beginTransaction();

    try {
        // $user = $createUser::run($request->validated(), [
        //     'role' => 'secretary'  // ✅ yahan se role bhejo
        // ]);
        $zone_id = auth()->user()->zone_id;
        // return $zone_id;
        $mediaPath = null;
        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store('media', 'public');
        }
        $user = $createUser::run(array_merge($request->validated(), [
                'role' => 'secretary' , // ✅ yahan se role bhejo
                'media' => $mediaPath,
                'zone_id'=>$zone_id,
            ]));
         $user->assignRole('secretary');

        $secretary = $createSecretary::run([
            'user_id' => $user->id,
            'neph'=>$request->input('neph'),
            'date_of_code'=>$request->input('date_of_code'),
            'status'  => $request->input('status'),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Secretary created successfully.',
            'data' => [
                'user' => $user,
                'secretary' => $secretary,
            ],
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while creating the secretary.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Store new secretary
     */
    // public function store(StoreUser $createUser, StoreSecretaryRepo $createSecretary, StoreSecretaryRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();

    //     try {
    //         $user = $createUser::run($request->validated());
    //         $user->assignRole('secretary');

    //         $createSecretary::run([
    //             'user_id' => $user->id,
    //             'status'  => $request->input('status'),
    //         ]);

    //         DB::commit();
    //         session()->flash('success', 'Le Secrétaire est bien ajouté');

    //         return redirect()->route('secretaries.index');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         session()->flash('error', 'Une erreur est survenue lors de la création du Secrétaire');
    //         throw $e;
    //     }
    // }

    /**
     * Edit secretary page
     */
    public function edit(Secretary $secretary): Response
    {
        return Inertia::render('features/general/dashboard/EditSecretary', [
            'secretary' => $secretary->load('user'),
        ]);
    }

    /**
     * Update secretary info
     */

    public function update(Request $request, Secretary $secretary){
            $validator = Validator::make($request->all(), [
        'first_name'     => 'required|string|max:255',
        'last_name'      => 'required|string|max:255',
        'status'         => 'nullable',
        'adresse'        => 'required|string|max:255',
        'phone'          => 'required|string|max:255',
        'sexe'           => 'required|string|max:255',
        'date_naissance' => 'required|string|max:255',
        'postal'         => 'nullable|string|max:255',
        'neph'           => 'nullable|string|max:255',
        'date_of_code'   => 'nullable|string|max:255',
        'media'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'email'          => 'email|unique:users,email,' . $secretary->user->id,
        'password'       => 'nullable|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors()
        ], 422);
    }

        DB::beginTransaction();

        try{
               $userData = $validator->validated();

                 if ($request->hasFile('media')) {

                        // optional: old file delete
                        if ($secretary->user->media) {
                            Storage::disk('public')->delete($secretary->user->media);
                        }

                        $userData['media'] = $request->file('media')->store('media', 'public');
                    }

                     $secretary->user->update($userData);

                    // ✅ 2nd table: secretaries update
                    $secretary->update([
                        'neph' => $request->input('neph'),
                        'date_of_code' => $request->input('date_of_code'),
                        'status' => $request->input('status'),
                    ]);

                    DB::commit();
                        return response()->json([
            'success' => true,
            'message' => 'Secretary updated successfully.',
            'data' => [
                'user' => $secretary->user->fresh(),
                'secretary' => $secretary->fresh(),
            ],
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error while updating secretary.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



//     public function update(StoreSecretaryRequest $request, Secretary $secretary)
//         {
//             DB::beginTransaction();

//             try {
//                 $secretary->user->update($request->validated());
//                 $secretary->update([
//                     'status' => $request->input('status')
//                 ]);

//                 DB::commit();

//                 return response()->json([
//                     'success' => true,
//                     'message' => 'Le Secrétaire a été modifié avec succès',
//                     'data' => $secretary->load('user')
//                 ], 200);

//             } catch (Exception $e) {
//                 DB::rollBack();

//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Erreur lors de la mise à jour du Secrétaire',
//                     'error' => $e->getMessage()
//                 ], 500);
//             }
// }
    // public function update(StoreUser $updateUser, StoreSecretaryRepo $updateSecretary, StoreSecretaryRequest $request, Secretary $secretary): RedirectResponse
    // {
    //     DB::beginTransaction();

    //     try {
    //         $secretary->user->update($request->validated());
    //         $secretary->update(['status' => $request->input('status')]);

    //         DB::commit();
    //         session()->flash('success', 'Le Secrétaire a été modifié avec succès');

    //         return redirect()->route('secretaries.index');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         session()->flash('error', 'Erreur lors de la mise à jour du Secrétaire');
    //         throw $e;
    //     }
    // }


    public function show(Secretary $secretary){
          $secretary->load('user');
    
        return response()->json([
            'success' => true,
            'data' => $secretary,
        ]);
    }
}
