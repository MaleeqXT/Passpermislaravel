<?php

namespace App\Http\Controllers\V1\Inertia\System\Promo;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\CreateOrUpdateOffreRequest;
use App\Models\Roles\Admin\Offer\Offer;
use App\Repository\V2\Shared\Base\Media\CreateMediaRepo;
use App\Repository\V2\Shared\Base\Media\DestroyModelMediaRepo;
use App\Repository\V2\Shared\Base\Media\EditMediaRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\DestroyOffreRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\EditOffreRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchOffresRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\StoreOffreRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class  OffresController extends Controller
{

    /**
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */

public function index(Request $request)
{
    $zoneIds = $request->input('zone_id', auth()->user()->zone_id);
    $zoneIds = is_array($zoneIds) ? $zoneIds : [$zoneIds];
    $zoneIds = array_values(array_filter($zoneIds));
    
    $filters = ['zone_id' => $zoneIds];

    // Active tab
    if ($request->has('status')) {
        $filters['status'] = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
    }
    
    // Archived tab
    
    // CPF tab
    if ($request->has('is_cpf')) {
        $filters['is_cpf'] = filter_var($request->input('is_cpf'), FILTER_VALIDATE_BOOLEAN);
    }
    
    // Pannier tab
    if ($request->has('is_cart')) {
        $filters['is_cart'] = filter_var($request->input('is_cart'), FILTER_VALIDATE_BOOLEAN);
    }

    // Default — koi tab nahi → active data

    $activeCountQuery = Offer::query();
    FetchAllOffreRepo::applyZoneFilter($activeCountQuery, $zoneIds);
    $activeCount = $activeCountQuery
        ->where('is_cpf', 0)->where('status', 1)->count();

    $archivedCountQuery = Offer::query();
    FetchAllOffreRepo::applyZoneFilter($archivedCountQuery, $zoneIds);
    $archivedCount = $archivedCountQuery
        ->where('is_cpf', 0)->where('status', 0)->count();

    $cpfCountQuery = Offer::query();
    FetchAllOffreRepo::applyZoneFilter($cpfCountQuery, $zoneIds);
    $cpfCount = $cpfCountQuery->where('is_cpf', 1)->count();

    $cartCountQuery = Offer::query();
    FetchAllOffreRepo::applyZoneFilter($cartCountQuery, $zoneIds);
    $cartCount = $cartCountQuery->where('is_cpf', 0)->where('status',1)->where('is_offer_cart', 1)->count();

    $allCountQuery = Offer::query();
    FetchAllOffreRepo::applyZoneFilter($allCountQuery, $zoneIds);
    $allCount = $allCountQuery->count();

    return response()->json([
        'success'        => true,
        'offers'         => FetchAllOffreRepo::run($filters),
        'active_count'   => $activeCount,
        'archived_count' => $archivedCount,
        'cpf_count'      => $cpfCount,
        'cart_count'     => $cartCount,
        'count_all'      => $allCount,
    ], 200);
}
        // public function index()
        // {
        //         $filters = array_merge(request()->all(), [
        //     'zone_id' => auth()->user()->zone_id,
        // ]);


        // $archivedCount = FetchAllOffreRepo::run(array_merge($filters, [
        //     'status' => false,
        //     'is_cpf' => false,
        // ]))->total();

        // $activeCount = FetchAllOffreRepo::run(array_merge($filters, [
        //     'status' => true,
        //     'is_cpf' => false,
        // ]))->total();

        // $cpfCount = FetchAllOffreRepo::run(array_merge($filters, [
        //     'is_cpf' => true
        // ]))->total();

        // $cartCount = FetchAllOffreRepo::run(array_merge($filters, [
        //     'is_cart' => true
        // ]))->total();

        // return response()->json([
        //     'success' => true,
        //     'offers' => FetchAllOffreRepo::run($filters),
        //     'active_count' => $activeCount,
        //     'archived_count' => $archivedCount,
        //     'cpf_count' => $cpfCount,
        //     'cart_count' => $cartCount,
         
        // ], 200);
        // }

    /**
     * @return Response
     */
    public function create(): Response
    {

        return Inertia::render('features/store/offers/OfferCreatePage');
    }


    /**
     * @param CreateOrUpdateOffreRequest $request
     * 
     * @throws Exception
     */

    public function store(CreateOrUpdateOffreRequest $request)
{
    DB::beginTransaction();

    try {
        $attributes = $request->except(['media', '_method']);

        $attributes['zone_id'] = is_array($attributes['zone_id'] ?? null)
            ? $attributes['zone_id']
            : [$attributes['zone_id'] ?? auth()->user()->zone_id];

     
        $offer = StoreOffreRepo::run($attributes);

        CreateMediaRepo::run($request->only('media'), $offer);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully.',
            'data' => $offer->fresh(),
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create offer.',
            'error' => $e->getMessage(), // Production mein remove kar sakte ho
        ], 500);
    }
}


    /**
     * @param Offer $offer
     * 
     */
public function edit(Offer $offer)
{
    $data = FetchOffresRepo::run($offer->id, request()->all());

        return response()->json([
            'success' => true,
            'message' => 'Offer fetched successfully',
            'data' => $data,
        ], 200);
}

    /**
     * @param CreateOrUpdateOffreRequest $request
     * @param Offer $offer
     * 
     * @throws Exception
     */

    public function update(CreateOrUpdateOffreRequest $request, Offer $offer)
{
    DB::beginTransaction();

    try {
        $attributes = $request->except(['media', '_method']);

        if (isset($attributes['zone_id']) && !is_array($attributes['zone_id'])) {
            $attributes['zone_id'] = [$attributes['zone_id']];
        }

        $updatedOffer = EditOffreRepo::run($offer, $attributes);

        if ($request->hasFile('media')) {
            EditMediaRepo::run([
                'media' => $request->file('media')
            ], $offer);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully.',
            'data' => $offer->fresh(['media.storageMedia']),
        ], 200);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update offer.',
            'error' => $e->getMessage(), // Production mein remove kar dena
        ], 500);
    }
}

    // public function update(CreateOrUpdateOffreRequest $request, Offer $offer): RedirectResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $attributes = $request->except('media');
    //         if (isset($attributes['agency_pricing'])) {
    //             $attributes['agency_pricing'] = json_encode($attributes['agency_pricing']);
    //         }
    //         $status = EditOffreRepo::run($offer, $attributes);
    //         if ($offer?->media?->storage_media_id != $request->get('media')) {
    //             EditMediaRepo::run($request->only('media'), $offer);
    //         }
    //         DB::commit();
    //         session()->flash('success', flashMessage());

    //         return redirect()->back();
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         session()->flash('error', flashMessage('error'));
    //         throw $e;
    //     }
    // }

    /**
     * @param CreateOrUpdateOffreRequest $request
     * @param Offer $offer
     * 
     * @throws Exception
     */
    public function updateStatus(Request $request, Offer $offer)
{
    DB::beginTransaction();

    try {
        $status = $request->get('status');

        EditOffreRepo::run($offer, [
            'status' => $status,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Offer status updated successfully.',
            'data' => $offer->fresh(),
        ], 200);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update offer status.',
            'error' => $e->getMessage(), // Production mein remove kar sakte ho
        ], 500);
    }
}



    /**
     * @param Offer $offer
     * @throws Exception
     */
  public function destroy(Offer $offer)
{
    // return $offer;
    DB::beginTransaction();

    try {

        DestroyModelMediaRepo::run($offer);

        DestroyOffreRepo::run($offer);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully.',
        ], 200);

    } catch (Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete offer.',
            'error' => $e->getMessage(), // Production mein remove kar dena
        ], 500);
    }
}
}
