<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\CandidateEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HeavyVehicleCandidateController extends Controller
{
    use HandlesRdvPermisErrors;

    /**
     * Swagger (RECETTE, 2026-11-19) request body RechercheCandidatEtPaginationApi:
     * `filtre` is optional (omit it for the full paginated list); when present,
     * `filtre.groupePermis` is required and must be CE for this route. `nom` and
     * `numeroDossier` are {query, match: PARTIAL|EXACT}. `parPage` is 1..100.
     */
    public function search(Request $request, CandidateEligibilityService $candidates): JsonResponse
    {
        $validated = $request->validate([
            'filtre' => ['nullable', 'array'],
            'filtre.groupePermis' => ['bail', 'required_with:filtre', 'in:CE'],
            'filtre.nom' => ['nullable', 'array'],
            'filtre.nom.query' => ['bail', 'required_with:filtre.nom', 'string'],
            'filtre.nom.match' => ['bail', 'required_with:filtre.nom', 'in:PARTIAL,EXACT'],
            'filtre.numeroDossier' => ['nullable', 'array'],
            'filtre.numeroDossier.query' => ['bail', 'required_with:filtre.numeroDossier', 'string'],
            'filtre.numeroDossier.match' => ['bail', 'required_with:filtre.numeroDossier', 'in:PARTIAL,EXACT'],
            'page' => ['nullable', 'integer', 'min:1'],
            'parPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $response = $candidates->searchHeavyVehicle($request->user(), $this->criteria($validated));

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return $this->providerError($exception);
        } catch (Throwable $exception) {
            return $this->internalError($exception);
        }
    }

    /** Rebuilds the provider body from validated fields only. */
    private function criteria(array $validated): array
    {
        $criteria = [];
        if (isset($validated['filtre'])) {
            $filtre = ['groupePermis' => $validated['filtre']['groupePermis']];
            foreach (['nom', 'numeroDossier'] as $field) {
                if (isset($validated['filtre'][$field])) {
                    $filtre[$field] = [
                        'query' => $validated['filtre'][$field]['query'],
                        'match' => $validated['filtre'][$field]['match'],
                    ];
                }
            }
            $criteria['filtre'] = $filtre;
        }
        foreach (['page', 'parPage'] as $field) {
            if (isset($validated[$field])) {
                $criteria[$field] = (int) $validated[$field];
            }
        }

        return $criteria;
    }
}
