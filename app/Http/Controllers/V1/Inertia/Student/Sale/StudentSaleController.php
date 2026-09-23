<?php

namespace App\Http\Controllers\V1\Inertia\Student\Sale;

use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Repository\V2\Shared\Schedule\Sale\FetchAllSaleRepo;
use App\Repository\V2\Shared\Schedule\Sale\FetchSaleRepo;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class StudentSaleController extends Controller
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index()
    {
        // Inertia::setRootView('espace-student');
        return Inertia::render('features/settings/commandes/CommandesPage', [
            'sales' => FetchAllSaleRepo::run(),
        ]);
    }


    /**
     * @param Sale $sale
     * @return Response
     */
    public function show(Sale $sale): \Inertia\Response
    {
        // Load the sale with related data
        $sale->load([
            'student.user',
            'cart.cartDetails' => function ($query) {
                $query->withTrashed()->with(['offer' => function ($q) {
                    $q->withTrashed();
                }]);
            }
        ]);

        // If there are multiple sales for the same payment, load them all
        // This handles the case where multiple offers were paid together
        $relatedSales = [];
        if ($sale->payment_id) {
            $relatedSales = Sale::where('payment_id', $sale->payment_id)
                ->with([
                    'student.user',
                    'cart.cartDetails' => function ($query) {
                        $query->withTrashed()->with(['offer' => function ($q) {
                            $q->withTrashed();
                        }]);
                    }
                ])
                ->get();
        }

        // Inertia::setRootView('espace-student');
       return Inertia::render('features/settings/commandes/CommandeShowPage', [
    'sale' => $sale,
    'sales' => count($relatedSales) > 1 ? $relatedSales : [],
    'hideBottomBar' => true
]);
    }



    /**
     * @param Billing $billing
     * @return Response
     */
   public function download(Sale $sale)
    {
        // Load the current sale with its cart and details
        $sale->load([
            'student.user',
            'cart.cartDetails' => function ($query) {
                $query->withTrashed()->with(['offer' => function ($q) {
                    $q->withTrashed();
                }]);
            }
        ]);

        // Load ALL sales with the same payment_id (related payments made at the same time)
        $relatedSales = Sale::where('payment_id', $sale->payment_id)
            ->with([
                'cart.cartDetails' => function ($query) {
                    $query->withTrashed()->with(['offer' => function ($q) {
                        $q->withTrashed();
                    }]);
                }
            ])
            ->get();

        // Add related sales to the sale data
        $saleData = $sale->toArray();
        $saleData['related_sales'] = $relatedSales->toArray();

        $pdf = PDF::loadView('pdf.normal.student-invoice', $saleData);
        return $pdf->stream('facture-' . Carbon::parse($sale['created_at'])->format('y-m-d') . '.pdf');
    }
}
