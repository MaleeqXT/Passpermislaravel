<?php

namespace App\Http\Controllers\V1\Inertia\System\AboutUs;

use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Contact\ContactUs;
use App\Repository\V2\Admin\Contact\EditContactRepo;
use App\Repository\V2\Admin\Contact\FetchAllContactRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;


class AboutUsController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {

        return Inertia::render('features/general/contact/ContactPage', [
            'contacts' => FetchAllContactRepo::run(request()->all()),
        ]);
    }

    /**
     * @param ContactUs $contactUs
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(ContactUs $contactUs): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditContactRepo::run($contactUs, request()->only(['is_read',]));
            DB::commit();
            session()->flash('success', flashMessage());

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
