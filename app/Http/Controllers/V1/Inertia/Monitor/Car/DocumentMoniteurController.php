<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\Info\Car\StoreOrUpdateDocumentRequest;
use App\Repository\V2\Monitor\Instructor\Certification\FetchAllCertificationRepo;
use App\Repository\V2\Monitor\Instructor\Certification\StoreOrEditCertificationRepo;
use App\Repository\V2\Monitor\Instructor\IdentityRecord\FetchIdentityRecordRepo;
use App\Repository\V2\Monitor\Instructor\IdentityRecord\StoreOrEditIdentityRecordRepo;
use App\Repository\V2\Monitor\Instructor\License\FetchAllLicenseRepo;
use App\Repository\V2\Monitor\Instructor\License\StoreOrEditLicenseRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DocumentMoniteurController extends Controller
{



    /**
     * 
     */
    // public function pieceIdentites(): Response
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/settings/documents/PieceIdentitesPage', [
    //         'data' => FetchIdentityRecordRepo::run(request()->all()),
    //         'hideBottomBar' => true,
    //     ]);
    // }
    public function pieceIdentites()
    {
        return response()->json([
            'data' => FetchIdentityRecordRepo::run(request()->all()),
            'hideBottomBar' => true,
        ], 200);
    }

    /**
     * 
     */

    public function permisConduire()
    {
        return response()->json([
            'data' => FetchAllLicenseRepo::run(request()->all()),
        ]);
    }
    // public function permisConduire(): Response
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/settings/documents/PermisConduitPage', [
    //         'data' => FetchAllLicenseRepo::run(request()->all()),
    //         'hideBottomBar' => true,

    //     ]);
    // }

    /**
     * 
     */
    public function diplom()
    {
        return response()->json([
            'data' => FetchAllCertificationRepo::run(request()->all()),
            'hideBottomBar' => true,
        ], 200);
    }

    // public function diplom(): Response
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/settings/documents/DiplomeEnseignantPage', [
    //         'data' => FetchAllCertificationRepo::run(request()->all()),
    //         'hideBottomBar' => true,
    //     ]);
    // }


    /**
     * @param StoreOrUpdateDocumentRequest $request
     * 
     * @throws Exception
     */
    public function storeOrUpdate(StoreOrUpdateDocumentRequest $request)
{
    DB::beginTransaction();

    try {
        $monitor = auth()->user()->monitor;

        StoreOrEditCertificationRepo::run(
            $monitor,
            $request->only('media_diplom')
        );

        StoreOrEditLicenseRepo::run(
            $monitor,
            $request->only('media_permis')
        );

        StoreOrEditIdentityRecordRepo::run(
            $monitor,
            $request->only('media_piece_identite')
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => flashMessage(),
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => flashMessage('error'),
            // 'error' => $e->getMessage(), // sirf development me
        ], 500);
    }
}
    // public function storeOrUpdate(StoreOrUpdateDocumentRequest $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $monitor = auth()->user()->monitor;
    //         StoreOrEditCertificationRepo::run($monitor, $request->only('media_diplom'));
    //         StoreOrEditLicenseRepo::run($monitor, $request->only('media_permis'));
    //         StoreOrEditIdentityRecordRepo::run($monitor, $request->only('media_piece_identite'));
    //         DB::commit();
    //         return redirect()->back()->with('success', flashMessage());
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }
//     public function storeOrUpdate(StoreOrUpdateDocumentRequest $request)
// {
//     DB::beginTransaction();

//     try {
//         $monitor = auth()->user()->monitor;

//         $documentPro = StoreOrEditInstructorDocumentRepo::run(
//             $monitor,
//             $request->except('autorisations')
//         );

//         if ($request->filled('autorisations')) {
//             StoreOrEditPermissionInstructorRepo::run(
//                 $documentPro,
//                 $request->get('autorisations')
//             );
//         }

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => flashMessage(),
//             'data' => $documentPro,
//         ], 200);

//     } catch (Exception $e) {
//         DB::rollBack();

//         return response()->json([
//             'success' => false,
//             'message' => flashMessage('error'),
//             // 'error' => $e->getMessage(), // sirf development me
//         ], 500);
//     }
// }
}
