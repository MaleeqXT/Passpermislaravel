<?php

namespace App\Http\Controllers\V1\EndPoint\System\Zone;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\Params\Zip\ZipCollection;
use App\Http\Resources\System\Params\Zone\ZoneCollection;
use App\Models\Roles\Admin\Area\Zone;
use app\models\User;
use App\Repository\V2\Admin\Zone\FetchAllZoneRepo;
use App\Repository\V2\Admin\Zone\Lieux\FetchAllLieuxRepo;
use App\Repository\V2\Admin\Zone\Zip\FetchAllZipsRepo;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * @return ZoneCollection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllZones(): ZoneCollection
    {
        return ZoneCollection::make(FetchAllZoneRepo::run(request()->all()));
    }

    /**
     * @param Zone $zone
     * @return ZipCollection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllZips(Zone $zone): ZipCollection
    {
        return ZipCollection::make(FetchAllZipsRepo::run($zone, request()->all()));
    }

    /**
     * @param Zone $zone
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllLieux(Zone $zone): LengthAwarePaginator
    {
        return FetchAllLieuxRepo::run($zone, request()->all());
    }

    


    public function getCandidates(Request $request)
{
    $query = User::query();

    // ✅ If "id" provided
    if ($request->filled('id')) {
        $query->where('id', $request->id);
    }

    // ✅ If "ville" provided
    if ($request->filled('ville')) {
        $query->where('ville', $request->ville);
    }

    // ✅ Return paginated candidates (users)
    $users = $query->paginate(20);

    return response()->json($users);
}

}
