<?php

namespace App\Http\Controllers\V1\Inertia\Admin;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchReservationDashboardRepo;
use App\Repository\V2\Shared\Schedule\Sale\FetchLastSaleRepo;
use Inertia\Inertia;
use Inertia\Response;

class HomePageController extends Controller
{


    /**
     * 
     */  
    // public function index()
    // {
       
    //     $date = now()->startOfMonth()->subMonth();

    //     return Inertia::render('features/general/dashboard/DashboardPage', [
    //         'totalPerMonth' => [
    //             'students' => Student::query()->whereDate('created_at', '>=', $date)->count(),
    //             'reservations' => Reservation::query()->whereDate('created_at', '>=', $date)->whereHas('training')->count(),
    //             'commmandes' => Sale::query()->whereDate('created_at', '>=', $date)->where('payment_status', SaleStatusEnum::PAID->value)->sum('amount'),
    //             'balances' => Wallet::query()->sum('balance'),
    //         ],
    //         'lastCommandes' => FetchLastSaleRepo::run(request()->all()),
    //         'lastReservations' => FetchReservationDashboardRepo::run(request()->all()),

    //     ]);
    // }

    public function index()
    {
        $date = now()->startOfMonth()->subMonth();
        $zoneId = request()->string('zone_id')->toString() ?: null;
        $students = Student::query();
        $reservations = Reservation::query();
        $sales = Sale::query();
        $wallets = Wallet::query();

        // The selected school is a Zone. Apply it consistently to every
        // dashboard card and list, just like the active-agency dashboard.
        if ($zoneId) {
            $students->whereHas('user', fn ($query) => $query->where('zone_id', $zoneId));
            $sales->whereHas('student.user', fn ($query) => $query->where('zone_id', $zoneId));
            $wallets->whereHas('student.user', fn ($query) => $query->where('zone_id', $zoneId));
            $reservations->whereHas('lieu', fn ($query) => $query->where('zone_id', $zoneId));
        }

        $data = [
            'totalPerMonth' => [
                'students' => $students->whereDate('created_at', '>=', $date)->count(),
                'reservations' => $reservations->whereDate('created_at', '>=', $date)->whereHas('training')->count(),
                'commmandes' => $sales->whereDate('created_at', '>=', $date)->where('payment_status', SaleStatusEnum::PAID->value)->sum('amount'),
                'balances' => $wallets->sum('balance'),
            ],
            'lastCommandes' => FetchLastSaleRepo::run(request()->all()),
            'lastReservations' => FetchReservationDashboardRepo::run(request()->all()),
        ];

        if (request()->routeIs('api.admin.*')) {
            return response()->json($data);
        }

        return Inertia::render('features/general/dashboard/DashboardPage', $data);
    }
}
