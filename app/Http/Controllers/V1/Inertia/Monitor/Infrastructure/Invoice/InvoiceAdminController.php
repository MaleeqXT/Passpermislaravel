<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Repository\V2\Monitor\Billing\EditInvoiceRepo;
use App\Repository\V2\Monitor\Billing\FetchAllInvoiceRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceAdminController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {


        return Inertia::render('features/settings/invoices/InvoicesPage', [
            'invoices' =>  FetchAllInvoiceRepo::run(request()->all())
        ]);
    }


    /**
     * @param Billing $billing
     * @return Response
     */
    public function show(Billing $billing): Response
    {

        return Inertia::render('features/settings/invoices/InvoiceViewPage', [
            'invoice' => $billing,
        ]);
    }


    /**
     * @param Billing $billing
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Billing $billing): RedirectResponse
    {
        DB::beginTransaction();
        try {
            EditInvoiceRepo::run($billing, request()->only(['date_paiement', 'status']));

            session()->flash('success', flashMessage());
            DB::commit();
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
