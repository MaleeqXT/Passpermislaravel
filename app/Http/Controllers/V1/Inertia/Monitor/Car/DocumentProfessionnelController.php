<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\Info\Car\StoreOrUpdateDocumentProRequest;
use App\Repository\V2\Monitor\Instructor\Document\FetchInstructorDocumentRepo;
use App\Repository\V2\Monitor\Instructor\Document\StoreOrEditInstructorDocumentRepo;
use App\Repository\V2\Monitor\Instructor\Permission\StoreOrEditPermissionInstructorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

use Inertia\Inertia;
use Inertia\Response;

class DocumentProfessionnelController extends Controller
{


    /**
     */
    // public function index()
    // {
    //     // Inertia::setRootView('espace-monitor');
    //     return Inertia::render('features/settings/documentsProfessionel/DocumentsProfessionnelPage', [
    //         'data' => FetchInstructorDocumentRepo::run(request()->all()),
    //         'hideBottomBar' => true
    //     ]);
    // }

public function index()
{
    return response()->json([
        'data' => FetchInstructorDocumentRepo::run(request()->all()),
        'hideBottomBar' => true,
    ], 200);
}



    /**
     * @param StoreOrUpdateDocumentProRequest $request
     * 
     * @throws Exception
     */
    // public function storeOrUpdate(StoreOrUpdateDocumentProRequest $request): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $monitor = auth()->user()->monitor;
    //         $documentPro = StoreOrEditInstructorDocumentRepo::run($monitor, $request->except('autorisations'));
    //         if ($request->get('autorisations'))
    //             StoreOrEditPermissionInstructorRepo::run($documentPro, $request->get('autorisations'));

    //         DB::commit();
    //         return redirect()->back()->with('success', flashMessage());
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }
    public function storeOrUpdate(StoreOrUpdateDocumentProRequest $request)
{

    DB::beginTransaction();

    try {
        $monitor = auth()->user()->monitor;

        $documentPro = StoreOrEditInstructorDocumentRepo::run(
            $monitor,
            $request->except('autorisations')
        );

        if ($request->filled('autorisations')) {
            StoreOrEditPermissionInstructorRepo::run(
                $documentPro,
                $request->get('autorisations')
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => flashMessage(),
            'data' => $documentPro,
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
}
