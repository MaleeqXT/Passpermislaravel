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
use Illuminate\Support\Facades\Cache;
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
        $zoneId = request()->string('zone_id')->toString() ?: null;
        $cacheKey = sprintf('admin-dashboard:%s:%s', auth()->id() ?? 'guest', $zoneId ?: 'all');

        // This endpoint powers the React dashboard. It is requested on every
        // dashboard visit, so keep its response short-lived while avoiding the
        // expensive relationship graph used by detailed reservation screens.
        $data = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($zoneId) {
            $date = now()->startOfMonth()->subMonth();
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

            $attributes = $zoneId ? ['zone_id' => $zoneId] : [];

            return [
                'totalPerMonth' => [
                    // Avoid wrapping indexed timestamps in DATE(), which
                    // forces a table scan as the dashboard data grows.
                    'students' => $students->where('created_at', '>=', $date)->count(),
                    'reservations' => $reservations->where('created_at', '>=', $date)->whereHas('training')->count(),
                    'commmandes' => $sales->where('created_at', '>=', $date)->where('payment_status', SaleStatusEnum::PAID->value)->sum('amount'),
                    'balances' => $wallets->sum('balance'),
                ],
                // The card only displays the purchaser name, amount, and date.
                'lastCommandes' => FetchLastSaleRepo::run($attributes, [
                    'student:id,user_id',
                    'student.user:id,name',
                ])->toArray(),
                // Do not load reviews, cancellations, or realised-hours here:
                // the dashboard only displays these six reservation summaries.
                'lastReservations' => FetchReservationDashboardRepo::run($attributes, [
                    'training:id,student_id,offer_id',
                    'training.student:id,user_id',
                    'training.student.user:id,name',
                    'training.offer:id,name',
                    // `lieux` stores the location URL, not an `address` column.
                    // Selecting the latter makes the whole dashboard request fail.
                    'lieu:id,name,url,zone_id',
                    'lieu.zone:id,name',
                    'monitor:id,user_id',
                    'monitor.user:id,name',
                ])->toArray(),
            ];
        });

        if (request()->routeIs('api.admin.*')) {
            return response()->json($data);
        }

        return Inertia::render('features/general/dashboard/DashboardPage', $data);
    }
}
