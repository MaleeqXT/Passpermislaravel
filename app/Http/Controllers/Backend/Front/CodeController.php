<?php

namespace App\Http\Controllers\Backend\Front;

use App\Enums\V2\Admin\Promo\PromoTypeEnum;
use App\Http\Controllers\Controller;
use App\Repository\V2\Admin\PromoExclu\FetchPromoRepo;
use Inertia\Inertia;
use Inertia\Response;

class CodeController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/code/CodePage', [
            "page" => FetchPromoRepo::run(['type' => PromoTypeEnum::CUSTOMIZE_CODE->value]),
        ]);
    }
}
