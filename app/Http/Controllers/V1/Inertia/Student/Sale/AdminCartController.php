<?php

namespace App\Http\Controllers\V1\Inertia\Student\Sale;

use App\Http\Controllers\Controller;
use App\Enums\V2\Student\User\StudentSituationStatusEnum;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\User;
use App\Repository\V2\Student\Schedule\Sale\Admin\DestroyCartItemRepo;
use App\Repository\V2\Student\Schedule\Sale\Admin\DestroyCartRepo;
use App\Repository\V2\Student\Schedule\Sale\Admin\FetchAllCartsRepo;
use App\Repository\V2\Student\Schedule\Sale\Admin\FetchCartRepo;
use App\Repository\V2\Student\User\FetchAllStudentRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class AdminCartController extends Controller
{

    /**
     * 
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index()
    {
        // Super admins can change the selected school in the frontend.
        $zoneId = request()->get('zone_id') ?: auth()->user()->zone_id;
        $filters = array_merge(request()->all(), ['zone_id' => $zoneId]);
        $baseQuery = Cart::query()
            ->when($zoneId, fn ($query) => $query->whereHas('student.user', fn ($userQuery) => $userQuery->where('zone_id', $zoneId)));

        return response()->json([
            'users' => FetchAllStudentRepo::run(['role' => 'student'], $zoneId),
            'carts' => FetchAllCartsRepo::run($filters),
            'counts' => [
                'all_count' => (clone $baseQuery)->count(),
                'onhold_count' => (clone $baseQuery)->where('status', 1)->count(),
                'paid_count' => (clone $baseQuery)->where('status', 2)->count(),
            ],
        ]);
    }


    /**
     * @param Cart $cart
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Cart $cart): \Illuminate\Http\JsonResponse
    {
        $cartData = FetchCartRepo::run($cart);

        // Fetch all sales related to this cart (one sale per offer when multiple paid together)
        $relatedSales = collect();
        if ($cartData && $cartData->sale && $cartData->sale->payment_id) {
            $relatedSales = \App\Models\Roles\Admin\Offer\Order\Sale::where('payment_id', $cartData->sale->payment_id)
                ->with(['cart.cartDetails.offer'])
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return response()->json([
            'cart' => $cartData,
            'sales' => $relatedSales->count() > 1 ? $relatedSales : []
        ]);
    }


    /**
     * @param Cart $cart
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Cart $cart): \Illuminate\Http\RedirectResponse
    {
        DB::beginTransaction();
        try {

            $status = DestroyCartRepo::run($cart);

            DB::commit();
            session()->flash('success', flashMessage());
            // return

            return redirect()->route('admin.carts.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }


    /**
     * @param CartDetail $cartDetail
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroyItem(CartDetail $cartDetail): \Illuminate\Http\RedirectResponse
    {
        DB::beginTransaction();
        try {

            DestroyCartItemRepo::run($cartDetail);

            DB::commit();
            session()->flash('success', flashMessage());
            // return
            return redirect()->route('admin.carts.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
