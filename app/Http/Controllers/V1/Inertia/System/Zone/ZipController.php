<?php

namespace App\Http\Controllers\V1\Inertia\System\Zone;


use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Params\Zone\Zip\StoreZipRequest;
use App\Models\Roles\Admin\Area\Zip;
use App\Models\Roles\Admin\Area\Zone;
use App\Repository\V2\Admin\Zone\FetchZoneRepo;
use App\Repository\V2\Admin\Zone\Zip\DestroyZipRepo;
use App\Repository\V2\Admin\Zone\Zip\EditZipRepo;
use App\Repository\V2\Admin\Zone\Zip\FetchAllZipsRepo;
use App\Repository\V2\Admin\Zone\Zip\FetchZipRepo;
use App\Repository\V2\Admin\Zone\Zip\StoreZipRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class  ZipController extends Controller
{

    /**
     * @param Zone $zone
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Zone $zone): Response
    {

        return Inertia::render('features/settings/locations/ZipsCodePage', [
            'zips' => FetchAllZipsRepo::run($zone, request()->all()),
            'zone' => $zone
        ]);
    }

    // /**
    //  * @param Zone $zone
    //  * @return Response
    //  */
    // public function create(Zone $zone): Response
    // {

    //     
    //     return Inertia::render('features/shop/zips/ZipCreatePage', [
    //         'zone' => FetchZoneRepo::run($zone, request()->all())
    //     ]);
    // }


    /**
     * @param Zone $zone
     * @param StoreZipRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(Zone $zone, StoreZipRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // create Zip
            $zip = StoreZipRepo::run($zone, $request->validated());

            DB::commit();
            session()->flash('success', 'Le Zip est bien Ajouter');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la création du Zip');
            throw $e;
        }
    }


    // /**
    //  * @param Zip $zip
    //  * @return Response
    //  */
    // public function edit(Zip $zip): Response
    // {
    //     
    //     return Inertia::render('features/shop/zips/ZipEditPage', [
    //         'zip' => FetchZipRepo::run($zip, request()->all())
    //     ]);
    // }

    /**
     * @param StoreZipRequest $request
     * @param Zip $zip
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(StoreZipRequest $request, Zip $zip): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $status = EditZipRepo::run($zip, $request->validated());

            DB::commit();
            session()->flash('success', 'Le Zip est bien Modifier');
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification du Zip');
            throw $e;
        }
    }


    /**
     * @param Zip $zip
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Zip $zip): RedirectResponse
    {
        DB::beginTransaction();
        try {

            // delete produit
            $status = DestroyZipRepo::run($zip);
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
