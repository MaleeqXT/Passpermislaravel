<?php

namespace App\Http\Controllers\V1\Inertia\System\Promo;

use App\Enums\V2\Admin\Promo\PromoTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Page\StoreOrUpdatePromoRequest;
use App\Models\Roles\Admin\Promo\Promo;
use App\Repository\V2\Admin\PromoExclu\AttachOffreOfPromoRepo;
use App\Repository\V2\Admin\PromoExclu\DestroyPromoRepo;
use App\Repository\V2\Admin\PromoExclu\EditOrCreatePromoRepo;
use App\Repository\V2\Admin\PromoExclu\EditPromoRepo;
use App\Repository\V2\Admin\PromoExclu\FetchAllPromosRepo;
use App\Repository\V2\Admin\PromoExclu\FetchPromoRepo;
use App\Repository\V2\Admin\PromoExclu\StorePromoRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchOffreRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class PromoController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {

        return Inertia::render('features/settings/pages/AllPromoPage', [
            "pages" => FetchAllPromosRepo::run(request()->all()),
        ]);
    }

    /**
     * @param Promo $promo
     * @return Response
     */
    public function edit(Promo $promo): Response
    {

        return Inertia::render('features/settings/pages/PromoEditPage', [
            "page" => $promo,
        ]);
    }

    /**
     * @return Response
     */
    public function create(): Response
    {

        return Inertia::render('features/settings/pages/PromoCreatePage');
    }

    /**
     * @return Response
     */
    public function homeCustomize(): Response
    {
        return Inertia::render('features/settings/pages/HomeCustomizePage', [
            "page" => FetchPromoRepo::run(['type' => PromoTypeEnum::CUSTOMIZE_HOME->value, 'extra' => true]),
        ]);
    }

    /**
     * @return Response
     */
    public function codeCustomize(): Response
    {
        return Inertia::render('features/settings/pages/CodeCustomizePage', [
            "page" => FetchPromoRepo::run(['type' => PromoTypeEnum::CUSTOMIZE_CODE->value]),
            "offers" => FetchAllOffreRepo::run(['type' => OffreTypeStatusEnum::CODE->value]),
        ]);
    }

    /**
     * @param Promo $promo
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update(Promo $promo): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // dd($promo->id, request()->get('offers'));

            $res = EditPromoRepo::run($promo, request()->except(['offers', 'is_affiliate']));
            AttachOffreOfPromoRepo::run($promo, request()->get('offers'));
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', flashMessage('error'));
        }
    }

    /**
     * @param Promo $promo
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generalUpdate(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $res = EditOrCreatePromoRepo::run(Request()->all());
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', flashMessage('error'));
        }
    }


    /**
     * @param StoreOrUpdatePromoRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function store(StoreOrUpdatePromoRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $promo = StorePromoRepo::run($request->except(['offers', 'is_affiliate']) + ['location' => 'home', 'type' => PromoTypeEnum::PROMO->value]);
            AttachOffreOfPromoRepo::run($promo, $request->get('offers'));
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->route('admin.pages.promo.index');
        } catch (Exception $e) {
            DB::rollback();
            redirect()->back()->with('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param Promo $promo
     * @return RedirectResponse
     */
    public function delete(Promo $promo): RedirectResponse
    {
        DB::beginTransaction();
        try {
            DestroyPromoRepo::run($promo);
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', flashMessage('error'));
        }
    }
}
