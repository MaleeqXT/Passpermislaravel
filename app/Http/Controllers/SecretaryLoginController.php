<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;

use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchReservationDashboardRepo;
use App\Repository\V2\Shared\Schedule\Sale\FetchLastSaleRepo;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class SecretaryLoginController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
          $date = now()->startOfMonth()->subMonth();
        // Inertia component ke path ko Vue file ke relative path ke hisaab se mention karo
            return Inertia::render('features/general/dashboard/DashboardPage',
             [
            'totalPerMonth' => [
                'students' => Student::query()->whereDate('created_at', '>=', $date)->count(),
                'reservations' => Reservation::query()->whereDate('created_at', '>=', $date)->whereHas('training')->count(),
                'commmandes' => Sale::query()->whereDate('created_at', '>=', $date)->where('payment_status', SaleStatusEnum::PAID->value)->sum('amount'),
                'balances' => Wallet::query()->sum('balance'),
            ],
            'lastCommandes' => FetchLastSaleRepo::run(request()->all()),
            'lastReservations' => FetchReservationDashboardRepo::run(request()->all()),

        ]);
    }


protected function redirectTo()
{
    $user = auth()->user();

    if ($user->hasRole('admin')) {
        return '/admin/dashboard';
    } elseif ($user->hasRole('secretary')) {
        // send secretary users to the dedicated secretary dashboard route
        return '/secretary/dashboard';
    } elseif ($user->hasRole('student')) {
        return '/student/dashboard';
    } else {
        return '/';
    }
}

public function profile()
{
    $user = auth()->user();

    if (! $user) {
        return response()->json(['error' => 'Not authenticated'], 401);
    }

    $isSecretary = \DB::table('secretaries')->where('user_id', $user->id)->exists();

    if (! $isSecretary) {
        return response()->json(['error' => 'Not a secretary'], 403);
    }

    return response()->json([
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
    ]);
}



}
