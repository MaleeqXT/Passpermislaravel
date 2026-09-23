<?php

namespace App\Http\Controllers\V1\Inertia\Student\Competence;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Competency\StoreOrUpdateMainCompetencyRequest;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use App\Repository\V2\Shared\Competency\Main\DestroyMainCompetencyRepo;
use App\Repository\V2\Shared\Competency\Main\EditMainCompetencyRepo;
use App\Repository\V2\Shared\Competency\Main\FetchMainCompetencyRepo;
use App\Repository\V2\Shared\Competency\Main\StoreMainCompetencyRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class  MainCompetenceAdminController extends Controller
{

    /**
     * 
     */


    public function index()
{      
        $activeCount = MainCompetency::where('zone_id', auth()->user()->zone_id)
            ->where('status', 1)
            ->count();

        $archiveCount = MainCompetency::where('zone_id', auth()->user()->zone_id)
            ->where('status', 0)
            ->count();

        $allCount = MainCompetency::where('zone_id', auth()->user()->zone_id)
            ->count();
    
        return response()->json([
            'success' => true,
            'activeCount' => $activeCount,
            'archiveCount' => $archiveCount,
            'allCount' => $allCount,
            
            'groups' => FetchMainCompetencyRepo::run(request()->all()),
        ], 200);
}


    /**
     * @param StoreOrUpdateMainCompetencyRequest $request
     * RedirectResponse
     * @throws Exception
     */

    public function store(StoreOrUpdateMainCompetencyRequest $request)
{
    DB::beginTransaction();

    try {
        $competency = StoreMainCompetencyRepo::run(array_merge($request->validated(), [
            'zone_id'=>auth()->user()->zone_id,
        ]));

        DB::commit();

        return response()->json([
            'success' => true,
            'data' => $competency,
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}
    // public function store(StoreOrUpdateMainCompetencyRequest $request): RedirectResponse
    // {

    //     DB::beginTransaction();
    //     try {
    //         StoreMainCompetencyRepo::run($request->validated());

    //         DB::commit();
    //         session()->flash('success', flashMessage());
    //         // return

    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }


    /**
     * @param StoreOrUpdateMainCompetencyRequest $request
     * @param MainCompetency $mainCompetency
     * 
     * @throws Exception
     */
    // public function update(StoreOrUpdateMainCompetencyRequest $request, MainCompetency $mainCompetency): RedirectResponse
    // {
      
    //     DB::beginTransaction();
    //     try {
    //         EditMainCompetencyRepo::run($mainCompetency, $request->validated());
    //         DB::commit();
    //         session()->flash('success', flashMessage());
    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }

    public function update(
    StoreOrUpdateMainCompetencyRequest $request,
    MainCompetency $mainCompetency
) {
    DB::beginTransaction();

    try {
        $updatedCompetency = EditMainCompetencyRepo::run(
            $mainCompetency,
            $request->validated()
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => flashMessage(),
            'data' => $updatedCompetency,
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => flashMessage('error'),
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * @param MainCompetency $mainCompetency
    
     * @throws Exception
     */
    // public function destroy(MainCompetency $mainCompetency): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {

    //         DestroyMainCompetencyRepo::run($mainCompetency);
    //         DB::commit();

    //         session()->flash('success', flashMessage());

    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }

    public function destroy(MainCompetency $mainCompetency)
{
    DB::beginTransaction();

    try {
        DestroyMainCompetencyRepo::run($mainCompetency);

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
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
