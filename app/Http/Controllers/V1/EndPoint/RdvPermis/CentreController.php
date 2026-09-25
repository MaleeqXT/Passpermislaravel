<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\CentreService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CentreController extends Controller
{
    public function search(Request $request, CentreService $centres): JsonResponse
    {
        $validated = $request->validate([
            'codeDepartement' => ['bail', 'required', 'string'],
            'groupePermis' => ['bail', 'required', 'in:A,B,CE'],
            'estFerme' => ['nullable', 'boolean'],
        ]);

        return $this->respond(fn () => $centres->search(
            $request->user(),
            $validated['codeDepartement'],
            $validated['groupePermis'],
            $validated['estFerme'] ?? null,
        ));
    }

    public function show(Request $request, string $centreId, CentreService $centres): JsonResponse
    {
        return $this->respond(fn () => $centres->find($request->user(), $centreId));
    }

    public function favorites(Request $request, CentreService $centres): JsonResponse
    {
        return $this->respond(fn () => $centres->favorites($request->user()));
    }

    public function saveFavorites(Request $request, CentreService $centres): JsonResponse
    {
        // The current OpenAPI schema makes the JSON body required but does not
        // formally require either property; preserve that provider contract.
        $payload = $request->validate([
            'groupePermis' => ['sometimes', 'in:A,B,CE'],
            'centresFavorisInput' => ['sometimes', 'array'],
            'centresFavorisInput.*.centreId' => ['sometimes', 'string'],
            'centresFavorisInput.*.priorite' => ['sometimes', 'integer', 'between:0,2'],
        ]);

        return $this->respond(fn () => $centres->saveFavorites($request->user(), $payload));
    }

    /** @param callable(): ClientResponse $operation */
    private function respond(callable $operation): JsonResponse
    {
        try {
            $response = $operation();

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
