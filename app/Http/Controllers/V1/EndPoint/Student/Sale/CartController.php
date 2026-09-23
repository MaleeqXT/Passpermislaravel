<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\Sale\Cart\CartRequest;
use App\Http\Requests\V1\Student\Offre\Sale\Cart\StoreCartManyItemsRequest;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Repository\V2\Student\Schedule\Sale\Admin\DestroyCartItemRepo;
use App\Repository\V2\Student\Schedule\Sale\CreateOrUpdateManyCartRepo;
use App\Repository\V2\Student\Schedule\Sale\FetchCartRepo;
use App\Repository\V2\Student\Schedule\Sale\StoreOrEditCartRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(FetchCartRepo $getCart, User $user = null)
    {
        // if (!$user && auth()->user()->student) {
        //     $user = auth()->user();
        // }
        // dd('1', $user->id);

        return response()->json($getCart::run($user));
    }


    /**
     * @param CartRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function storeOrUpdate(CartRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $cart = StoreOrEditCartRepo::run($request->validated());

            DB::commit();

            // return
            return response()->json([
                'cart' => $cart,
                'message' => 'Le produit a été ajouté au panier'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    /**
     * @param CartDetail $cartDetail
     * @return JsonResponse
     * @throws Exception
     */

    public function destroy(CartDetail $cartDetail): JsonResponse
    {
        DB::beginTransaction();
        try {

            $status = DestroyCartItemRepo::run($cartDetail);

            DB::commit();
            // return
            return response()->json([
                'message' => 'Le produit a été supprimé du panier'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update cart detail with installment/price selection
     * @param $offerId
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCartDetail($offerId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Find cart for current user
            $cart = $user->cart()->first();
            if (!$cart) {
                return response()->json(['error' => 'Cart not found'], 404);
            }

            // Find cart detail for this offer
            $cartDetail = CartDetail::where('cart_id', $cart->id)
                ->where('offres_id', $offerId)
                ->first();

            if (!$cartDetail) {
                return response()->json(['error' => 'Cart item not found'], 404);
            }

            // Update tranches, selected_price_type, and selected_installment_no
            $cartDetail->update([
                'tranches' => request()->input('tranches', $cartDetail->tranches),
                'selected_price_type' => request()->input('selected_price_type', $cartDetail->selected_price_type),
                'selected_installment_no' => request()->input('selected_installment_no', $cartDetail->selected_installment_no),
            ]);

            DB::commit();

            return response()->json([
                'cartDetail' => $cartDetail,
                'message' => 'Cart item updated successfully'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @param StoreCartManyItemsRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws Exception
     */
    public function storeOrUpdateMany(StoreCartManyItemsRequest $request, ?User $user = null)
    {
        DB::beginTransaction();
        try {
            $targetStudent = $this->resolveTargetStudent($request->input('student_id'));
            $cart = CreateOrUpdateManyCartRepo::run($request->get('data'), $user, $targetStudent);

            DB::commit();

            // return
            return response()->json([
                'cart' => $cart,
                'message' => 'Les produits ont été ajoutés au panier'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /** Resolve the dashboard student without allowing ordinary students to impersonate another profile. */
    private function resolveTargetStudent(?string $requestedStudentId = null): ?Student
    {
        $user = auth()->user();
        $student = $user?->student ?? Student::query()->where('user_id', $user?->id)->first();

        if (!$student && $requestedStudentId && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($requestedStudentId);
        }

        if (!$student) {
            throw new \RuntimeException('Profil élève introuvable pour cette commande.');
        }

        return $student;
    }
}
