<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Repository\V2\Monitor\Billing\FetchAllInvoiceRepo;
use App\Repository\V2\Monitor\Billing\SumTotalHourInvoiceRepo;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceMonitorController extends Controller
{


    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function menu(): Response
    {
        $monitor = Auth::user()->monitor;
        $period = request()->get('period') ?? now()->subMonth()->format('Y-m');
        // resources/espace-monitor/features/settings/invoices/InvoicesMenuPage.vue
        Inertia::setRootView('espace-monitor');
        //  resources/espace-monitor/features/settings/invoices/InvoicesHistorique.vue
        return Inertia::render('features/settings/invoices/InvoicesHistorique', [
            'invoice' => [
                'facturable_hour' =>  SumTotalHourInvoiceRepo::run($monitor, ['is_facturable' => true, 'period' => $period]),
                'not_facturable_hour' =>  SumTotalHourInvoiceRepo::run($monitor, ['is_not_facturable' => true,  'period' => $period])
            ],
            'invoices' => FetchAllInvoiceRepo::run(request()->all()),
        ]);
    }

    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {
        $period = request()->get('period') ?? now()->subMonth()->format('Y-m');
        $monitor = Auth::user()->monitor;
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/invoices/InvoicesPage', [
            'totalHour' => SumTotalHourInvoiceRepo::run($monitor, request()->all() + ['period' => $period]),
            'hideBottomBar' => true
        ]);
    }

    /**
     * @return Response
     */
    public function historique(): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/invoices/InvoicesHistorique', [
            'invoices' => FetchAllInvoiceRepo::run(request()->all()),
            'hideBottomBar' => true
        ]);
    }

    /**
     * @param Billing $billing
     * @return Response
     */
    public function view(Billing $billing): Response
    {
        // Inertia::setRootView('espace-monitor');
        return Inertia::render('features/settings/invoices/InvoiceViewPage', [
            'invoice' => $billing,
            'hideBottomBar' => true
        ]);
    }

    /**
     * @param Billing $billing
     * @return Response
     */
    public function download(Billing $billing)
    {
        $billing->load('monitor.user');

        $pdf = PDF::loadView('pdf.normal.monitor-invoice', [
            'monitor' => $billing->monitor?->toArray(),
            'user' => $billing->monitor?->user?->toArray(),
            'invoice' => $billing->toArray(),
        ]);

        return $pdf->stream('facture-' . Carbon::parse($billing['to'])->format('y-m-d') . '.pdf');
    }
}
