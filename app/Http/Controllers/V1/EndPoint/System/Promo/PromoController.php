<?php

namespace App\Http\Controllers\V1\EndPoint\System\Promo;


use App\Enums\V2\Admin\Promo\PromoTypeEnum;
use App\Http\Controllers\Controller;
use App\Repository\V2\Admin\PromoExclu\FetchPromoRepo;
use Illuminate\Http\JsonResponse;

class PromoController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function home(): JsonResponse
    {
        return response()->json(["data" => FetchPromoRepo::run(['type' => PromoTypeEnum::CUSTOMIZE_HOME->value, 'extra' => true])]);
    }
}
