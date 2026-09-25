<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\PanierService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PanierController extends Controller
{
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

        return $this->respond(fn () => $paniers->assignCandidate(
            $request->user(),
            $panierId,
            $creneauId,
            $validated['candidatId'],
        ));
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
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
