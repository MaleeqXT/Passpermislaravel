<?php

namespace App\Http\Controllers\Backend\Front\Offers;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
class OffersController extends Controller
{
    /** Public JSON endpoint used by the React packages page. */
    public function apiIndex(): JsonResponse
    {
        return response()->json([
            'data' => FetchAllOffreRepo::run(request()->all()),
        ]);
    }

public function index()
{
    $user = Auth::user();

    // Allow unauthenticated users to view offers
    if (!$user) {
        $defaultParams = [
            'is_auto' => 0,
        ];

        $params = request()->all();
        if (empty($params)) {
            $params = $defaultParams;
        }

        $agency = $params['agency'] ?? null;

        Inertia::setRootView('espace-client');
        return Inertia::render('features/offers/OffersPage', [
            'offers' => FetchAllOffreRepo::run($params),
            'agency' => $agency,
        ]);
    }

    // Allow admin access
    if ($user->email === 'admin@pf.com') {
        // Continue with normal flow
    } else {
        // Allow only students from Creil (60100) and Toulouse (31300)
        $isCreil = strtolower($user->ville) === 'creil' && $user->postal === '60100';
        $isToulouse = strtolower($user->ville) === 'toulouse' && $user->postal === '31300';

        if (!($isCreil || $isToulouse)) {
            abort(403, 'Access denied. This offers page is only available for students in Creil (60100) and Toulouse (31300).');
        }
    }

    $defaultParams = [
        'is_auto' => 0,
    ];

    $params = request()->all();
    if (empty($params)) {
        $params = $defaultParams;
    }

    $agency = $params['agency'] ?? null;

    // Filter offers based on agency
    if ($agency) {
        $params['agency'] = strtolower($agency);

        if (!in_array($params['agency'], ['creil', 'toulouse'])) {
            abort(403, 'Invalid agency selected.');
        }
    }

    Inertia::setRootView('espace-client');
    return Inertia::render('features/offers/OffersPage', [
        'offers' => FetchAllOffreRepo::run($params),
        'agency' => $agency, // 👈 Vue ko bhej diya
    ]);
}

}

