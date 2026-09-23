<?php

namespace App\Http\Controllers\V1\EndPoint\System\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Params\Media\StoreManyMediaRequest;
use App\Http\Requests\V1\Admin\Params\Media\StoreOneMediaRequest;
use App\Http\Resources\System\StorageMedia\StorageMediaCollection;
use App\Http\Resources\System\StorageMedia\StorageMediaResource;
use App\Models\Media\Media;
use App\Models\Media\StorageMedia;
use App\Repository\V2\Shared\Base\Media\DestroyMediaRepo as DeleteMediaModel;
use App\Repository\V2\Shared\Base\StorageMedia\DestroyAllMediaRepo;
use App\Repository\V2\Shared\Base\StorageMedia\DestroyMediaRepo;
use App\Repository\V2\Shared\Base\StorageMedia\FetchAllMediaRepo;
use App\Repository\V2\Shared\Base\StorageMedia\FetchMediaRepo;
use App\Repository\V2\Shared\Base\StorageMedia\StoreManyMediaRepo;
use App\Repository\V2\Shared\Base\StorageMedia\StoreMediaRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{

    /**
     * @param Request $request
     * @return StorageMediaCollection
     */
    public function showAll(Request $request): StorageMediaCollection
    {
        return StorageMediaCollection::make(FetchAllMediaRepo::run($request->all()));
    }

    /**
     * @param string $media_id
     * @return StorageMediaResource
     */
    public function show(string $media_id): StorageMediaResource
    {
        return StorageMediaResource::make(FetchMediaRepo::run($media_id));
    }

    /**
     * @param StoreOneMediaRequest $request
     * @return StorageMediaResource|JsonResponse
     * @throws Exception
     */
    public function store(StoreOneMediaRequest $request): JsonResponse|StorageMediaResource
    {
        DB::beginTransaction();
        try {
            $media = StoreMediaRepo::run($request);
            DB::commit();
            return StorageMediaResource::make($media);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage() ?? 'Une erreur est survenue lors de la creation de l\'media');

            throw $e;
        }
    }


    /**
     * @param StoreManyMediaRequest $request
     * @return JsonResponse|null
     * @throws Exception
     */
    public function storeMany(StoreManyMediaRequest $request): ?JsonResponse
    {
        DB::beginTransaction();
        try {
            StoreManyMediaRepo::run($request);
            DB::commit();
            return response()->json([
                'message' => 'Media created successfully',
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage() ?? 'Une erreur est survenue lors de la creation de l\'media');

            throw $e;
        }
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function destroyMany(): JsonResponse
    {
        DB::beginTransaction();
        try {
            DestroyAllMediaRepo::run();
            DB::commit();
            return response()->json([
                'message' => 'Media Deleted successfully',
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de la suppression de l\'media');
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function destroy(StorageMedia $storageMedia)
    {
        DB::beginTransaction();
        try {
            DestroyMediaRepo::run($storageMedia);
            DB::commit();
            return response()->json([
                'message' => 'Votre media est bien supprimer',
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de la suppression de l\'media');

            throw $e;
        }
    }


    /**
     * @param Media $media
     * @return JsonResponse|RedirectResponse
     * @throws \Throwable
     */
    public function deleteMediaModel(Media $media): JsonResponse|RedirectResponse
    {

        DB::beginTransaction();
        try {
            // delete media
            $status = DeleteMediaModel::run($media);

            DB::commit();
            session()->flash('success', 'Media est bien supprimer');

            // return
            return response()->json([
                'status' => $status,
                'message' => 'Media est bien supprimer'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            // session()->flash('error', 'Une erreur est survenue lors de la suppression de l\'media');
            return response()->json([
                'status' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
}
