<?php

namespace App\Http\Controllers\V1\Inertia\Student\Sale;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Repository\V2\Shared\Schedule\Sale\FetchAllSaleRepo;
use App\Repository\V2\Shared\Schedule\Sale\FetchSaleStatsRepo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AdminSaleController extends Controller
{
    protected $fetchSaleStatsRepo;

    public function __construct(FetchSaleStatsRepo $fetchSaleStatsRepo)
    {
        $this->fetchSaleStatsRepo = $fetchSaleStatsRepo;
    }

    public function index()
    {
        // A super admin can switch school in the frontend; otherwise use the
        // authenticated user's assigned zone.
        $zoneId = request()->get('zone_id') ?: auth()->user()->zone_id;
        $filters = array_merge(request()->all(), [
            'zone_id' => $zoneId,
        ]);
        // Keep the revenue-card date range independent from the table filters.
        $statsFilters = array_merge($filters, [
            'start' => request()->get('stats_start') ?: ($filters['start'] ?? null),
            'end' => request()->get('stats_end') ?: ($filters['end'] ?? null),
        ]);

        $baseQuery = Sale::query()
            ->when($zoneId, fn (Builder $query) => $query->whereHas('student.user', fn (Builder $userQuery) => $userQuery->where('zone_id', $zoneId)));

        return response()->json([
            'sales' => FetchAllSaleRepo::run($filters),
            'stats' => $this->fetchSaleStatsRepo->run($statsFilters),
            'counts' => [
                'countAll' => (clone $baseQuery)->count(),
                'onHold_Count' => (clone $baseQuery)->where('payment_status', SaleStatusEnum::PENDING->value)->count(),
                'paid_Count' => (clone $baseQuery)->where('payment_status', SaleStatusEnum::PAID->value)->count(),
                'refunded_count' => (clone $baseQuery)->where('payment_status', SaleStatusEnum::REFUNDED->value)->count(),
                'canceled_count' => (clone $baseQuery)->where('payment_status', SaleStatusEnum::CANCELED->value)->count(),
            ],
        ]);
    }


    // public function index()
    // {
    //     return Inertia::render('features/store/commandes/CommandesPage', [
    //         'sales' => FetchAllSaleRepo::run(),
    //         'stats' => $this->fetchSaleStatsRepo->run(request()->all())
    //     ]);


    // }


    public function show(Sale $sale): JsonResponse
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
        $relatedSales = Sale::where('payment_id', $sale->payment_id)
    ->orderBy('id') // VERY IMPORTANT
    ->with([
        'student.user',
        'cart.cartDetails' => function ($query) {
            $query->withTrashed()->with(['offer' => function ($q) {
                $q->withTrashed();
            }]);
        }
    ])
    ->get();

    
        return response()->json([
            'sale' => $sale,
            'sales' => $relatedSales->count() > 1 ? $relatedSales : [] // only pass if multiple
        ]);
    }
}
