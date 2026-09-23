<?php

namespace App\Http\Controllers\V1\Inertia\System\Zone;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Params\Zone\Lieux\StoreLieuxRequest;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Admin\Area\Zone;
use App\Repository\V2\Admin\Zone\FetchZoneRepo;
use App\Repository\V2\Admin\Zone\Lieux\DestroyLieuxRepo;
use App\Repository\V2\Admin\Zone\Lieux\EditLieuRepo;
use App\Repository\V2\Admin\Zone\Lieux\FetchAllLieuxRepo;
use App\Repository\V2\Admin\Zone\Lieux\FetchLieuRepo;
use App\Repository\V2\Admin\Zone\Lieux\StoreLieuxRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class  LieuxController extends Controller
{

    /**
     * @param Zone $zone
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
public function index(Zone $zone)
{
    $allCount = $zone->lieux()->where('zone_id', $zone->id)->count();
    $activeCount = $zone->lieux()->where('zone_id', $zone->id)->where('status', 1)->count();
    $archiveCount = $zone->lieux()->where('zone_id', $zone->id)->where('status', 0)->count();

    return response()->json([
        'success' => true,
        'lieux' => FetchAllLieuxRepo::run($zone, request()->all()),
        'allCount' => $allCount,
        'activeCount' => $activeCount,
        'archiveCount' => $archiveCount,
        'zone' => $zone,
    ], 200);
}

    // /**
    //  * @param Zone $zone
    //  * @return Response
    //  */
    // public function create(Zone $zone): Response
    // {

    //     
    //     return Inertia::render('features/shop/lieux/LieuCreatePage', [
    //         'zone' => FetchZoneRepo::run($zone, request()->all())
    //     ]);
    // }


    /**
     * @param Zone $zone
     * @param StoreLieuxRequest $request
     * 
     * @throws Exception
     */
public function store(Zone $zone, StoreLieuxRequest $request)
{
    DB::beginTransaction();

    try {
        $lieu = StoreLieuxRepo::run($zone, $request->validated());

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Place created successfully.',
            'data' => $lieu,
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create place.',
            'error' => $e->getMessage(), // Production mein remove kar sakte ho
        ], 500);
    }
}


    // /**
    //  * @param Lieu $lieu
    //  * @return Response
    //  */
    // public function edit(Lieu $lieu): Response
    // {
    //     
    //     return Inertia::render('features/shop/lieux/LieuEditPage', [
    //         'lieu' => FetchLieuRepo::run($lieu, request()->all())
    //     ]);
    // }

    /**
     * @param StoreLieuxRequest $request
     * @param Lieu $lieu
     * 
     * @throws Exception
     */
  public function update(StoreLieuxRequest $request, Lieu $lieu)
{
    DB::beginTransaction();

    try {
        $updatedLieu = EditLieuRepo::run($lieu, $request->validated());

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Place updated successfully.',
            'data' => $updatedLieu,
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update place.',
            'error' => $e->getMessage(), // Production mein hata sakte ho
        ], 500);
    }
}

    /**
     * @param Lieu $lieu
     * 
     * @throws Exception
     */
  public function destroy(Lieu $lieu)
{
    DB::beginTransaction();

    try {
        DestroyLieuxRepo::run($lieu);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Place deleted successfully.',
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete place.',
            'error' => $e->getMessage(), // Production mein remove kar sakte ho
        ], 500);
    }
}
}
