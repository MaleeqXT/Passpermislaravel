<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\PanierService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanierController extends Controller
{
    use HandlesRdvPermisErrors;

    public function index(Request $request, PanierService $paniers): JsonResponse
    {
        return $this->respond(fn () => $paniers->all($request->user()));
    }

    public function store(Request $request, PanierService $paniers): JsonResponse
    {
        $validated = $request->validate([
            'employeAutoEcoleId' => ['nullable', 'uuid'],
        ]);

        return $this->respond(fn () => $paniers->create($request->user(), $validated['employeAutoEcoleId'] ?? null));
    }

    public function show(Request $request, string $panierId, PanierService $paniers): JsonResponse
    {
        return $this->respond(fn () => $paniers->find($request->user(), $panierId));
    }

    public function destroy(Request $request, string $panierId, PanierService $paniers): JsonResponse
    {
        return $this->respond(fn () => $paniers->delete($request->user(), $panierId));
    }

    public function validatePanier(Request $request, string $panierId, PanierService $paniers): JsonResponse
    {
        return $this->respond(fn () => $paniers->validate($request->user(), $panierId));
    }

    public function addSlot(Request $request, string $panierId, PanierService $paniers): JsonResponse
    {
        $validated = $request->validate([
            'creneauId' => ['bail', 'required', 'string'],
            'inclureEstCandidatObligatoire' => ['nullable', 'boolean'],
        ]);

        return $this->respond(fn () => $paniers->addSlot(
            $request->user(),
            $panierId,
            $validated['creneauId'],
            $validated['inclureEstCandidatObligatoire'] ?? null,
        ));
    }

    public function addSlots(Request $request, string $panierId, PanierService $paniers): JsonResponse
    {
        $validated = $request->validate([
            'creneauxId' => ['bail', 'required', 'array', 'min:1', 'max:12'],
            'creneauxId.*' => ['bail', 'required', 'string'],
            'inclureEstCandidatObligatoire' => ['nullable', 'boolean'],
        ]);

        return $this->respond(fn () => $paniers->addSlots(
            $request->user(),
            $panierId,
            $validated['creneauxId'],
            $validated['inclureEstCandidatObligatoire'] ?? null,
        ));
    }

    public function removeSlot(Request $request, string $panierId, string $creneauId, PanierService $paniers): JsonResponse
    {
        return $this->respond(fn () => $paniers->removeSlot($request->user(), $panierId, $creneauId));
    }

    public function assignCandidate(Request $request, string $panierId, string $creneauId, PanierService $paniers): JsonResponse
    {
        $validated = $request->validate([
            'candidatId' => ['present', 'nullable', 'string'],
        ]);

        return $this->respond(function () use ($request, $paniers, $panierId, $creneauId, $validated) {
            if ($validated['candidatId'] !== null) {
                app(\App\Services\RdvPermis\CandidateEligibilityService::class)
                    ->requireEligible($request->user(), $creneauId, $validated['candidatId']);
            }
            // Eligibility may change between requests; assignment remains provider-authoritative.
            return $paniers->assignCandidate($request->user(), $panierId, $creneauId, $validated['candidatId']);
        });
    }

    /** @param callable(): ClientResponse $operation */
    private function respond(callable $operation): JsonResponse
    {
        try {
            $response = $operation();
            $headers = [];

            if (($location = $response->header('Location')) !== null) {
                $headers['Location'] = $location;
            }

            return response()->json($response->json(), $response->status(), $headers);
        } catch (RdvPermisApiException $exception) {
            return $this->providerError($exception);
        } catch (\Throwable $exception) {
            return $this->internalError($exception);
        }
    }
}
