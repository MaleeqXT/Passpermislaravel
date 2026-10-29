<?php

namespace App\Http\Controllers\V1\Inertia\Student\Sale;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;

class StoreOffresController extends Controller
{
    /** JSON catalogue used by the React student dashboard. */
    public function apiIndex(): JsonResponse
    {
        $user = Auth::user();
        $student = $user?->student;
        $requestedStudentId = request()->validate([
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
        ])['student_id'] ?? null;

        if (!$student && $requestedStudentId && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($requestedStudentId);
            $user = $student?->user;
        }

        // The student profile is mandatory here: never return a mixed BM/BA
        // catalogue when we cannot identify the concerned student.
        $boiteType = $student?->boite_type;
        if (!in_array($boiteType, [0, 1, '0', '1'], true)) {
            return response()->json([
                'message' => 'Le type de boîte de l’élève est introuvable.',
                'offers' => [],
            ], 422);
        }

        $boiteType = (int) $boiteType;
        $zoneId = $user?->zone_id;

        if (!$zoneId && $user?->ville) {
            $zoneId = Zone::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($user->ville)])
                ->value('id');
        }

        if (!$zoneId) {
            return response()->json([
                'message' => 'Aucune agence n’est associée à votre profil.',
                'offers' => [],
            ], 422);
        }

        $offers = FetchAllOffreRepo::run([
            ...request()->all(),
            'zone_id' => $zoneId,
            'status' => 1,
            // 0 = boîte manuelle (BM), 1 = boîte automatique (BA).
            // Explicitly pass it because a staff account can request offers for
            // another selected student.
            'boite_type' => $boiteType,
        ]);

        // CPF catalogue records use their own publication lifecycle. When a
        // learner reaches checkout with a CPF item selected on the public site,
        // include the existing CPF offers for that learner's gearbox so the
        // cart can be rehydrated and paid through the normal checkout flow.
        $cpfOffers = collect();
        if (request()->boolean('include_cpf')) {
            $cpfOffers = Offer::query()
                ->where('is_cpf', true)
                ->where('is_auto', $boiteType)
                ->where('type_offre', 1)
                ->get();
        }

        // Defence in depth: this response is consumed by React directly.
        // Keep only the licence type assigned to this student even if a future
        // repository change broadens the base offer query.
        // "Pass Permis à la carte" is the existing menu offer behind the
        // additional-services page. It deliberately contains both BM and BA
        // service lines, so it must remain available to either student type.
        // It is still the same database offer and is not a second catalogue.
        $menuOffers = Offer::query()
            ->where('status', true)
            ->where('name', 'Pass Permis à la carte')
            ->get();

        $offers->setCollection(
            $offers->getCollection()
                ->merge($menuOffers)
                ->merge($cpfOffers)
                ->unique('id')
                ->filter(fn ($offer) => (int) $offer->is_auto === $boiteType || $offer->name === 'Pass Permis à la carte')
                ->values()
        );

        return response()->json([
            'offers' => $offers,
            'zone_id' => $zoneId,
            'boite_type' => $boiteType,
        ]);
    }

    /**
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
  public function index(): Response
    {
        $user = Auth::user();

        // Allow admin access
        if ($user->email === 'admin@pf.com') {
            return Inertia::render('features/shop/ShopPage', [
                'offers' => FetchAllOffreRepo::run(request()->all())
            ]);
        }

        // Allow only students from Creil (60100) and Toulouse (31300)
        $isCreil = strtolower($user->ville) === 'creil' && $user->postal === '60100';
        $isToulouse = strtolower($user->ville) === 'toulouse' && $user->postal === '31300';

        if (!($isCreil || $isToulouse)) {
            abort(403, 'Access denied. This shop is only available for students in Creil (60100) and Toulouse (31300).');
        }

        // Inertia::setRootView('espace-student');
        return Inertia::render('features/shop/ShopPage', [
            'offers' => FetchAllOffreRepo::run(request()->all())
        ]);
    }
}
