<?php

namespace App\Http\Controllers\V1\Inertia\Student\Competence;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Competency\StoreOrUpdateCompetencyRequest;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use App\Repository\V2\Shared\Competency\DestroyCompetencyRepo;
use App\Repository\V2\Shared\Competency\EditCompetencyRepo;
use App\Repository\V2\Shared\Competency\FetchCompetencyRepo;
use App\Repository\V2\Shared\Competency\StoreCompetencyRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class  CompetenceAdminController extends Controller
{

    /**
     * @param MainCompetency $mainCompetency
     * 
     */

        public function index(MainCompetency $mainCompetency)
        {
             $activeCount = Competency::where('main_competency_id',$mainCompetency->id)
            ->where('status', 1)
            ->count();

        $archiveCount = Competency::where('main_competency_id',$mainCompetency->id)
            ->where('status', 0)
            ->count();

        $allCount = Competency::where('main_competency_id',$mainCompetency->id)->count();

            return response()->json([
                'success' => true,
                'activeCount' => $activeCount,
                'archiveCount' => $archiveCount,
                'allCount' => $allCount,

                'subCompetencies' => FetchCompetencyRepo::run($mainCompetency, request()->all()),
                'group' => $mainCompetency,
            ], 200);
        }
    /**
     * @param MainCompetency $mainCompetency
     * @param StoreOrUpdateCompetencyRequest $request
     *
     * @throws Exception
     */
public function store(
    MainCompetency $mainCompetency,
    StoreOrUpdateCompetencyRequest $request
) {

    DB::beginTransaction();



    try {
        $competency = StoreCompetencyRepo::run(
            $mainCompetency,
            $request->validated()
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Competency created successfully',
            'data' => $competency,
        ], 201);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
        ], 500);
    }
}

    /**
     * @param StoreOrUpdateCompetencyRequest $request
     * @param Competency $competency
     * @return RedirectResponse
     * @throws Exception
     */
    // public function update(StoreOrUpdateCompetencyRequest $request, Competency $competency): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         EditCompetencyRepo::run($competency, $request->validated());

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
    StoreOrUpdateCompetencyRequest $request,
    Competency $competency
) {
    DB::beginTransaction();

    try {
        $updatedCompetency = EditCompetencyRepo::run(
            $competency,
            $request->validated()
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Competency updated successfully.',
            'data' => $updatedCompetency,
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update competency.',
            'error' => $e->getMessage(), // Production mein is line ko hata sakte hain.
        ], 500);
    }
}

    /**
     * @param Competency $competency
     * 
     * @throws Exception
     */
    // public function destroy(Competency $competency): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {

    //         DestroyCompetencyRepo::run($competency);
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

    public function destroy(Competency $competency)
{
    DB::beginTransaction();

    try {
        DestroyCompetencyRepo::run($competency);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Competency deleted successfully.',
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete competency.',
            'error' => $e->getMessage(), // Production mein isko remove kar sakte hain.
        ], 500);
    }
}
}
