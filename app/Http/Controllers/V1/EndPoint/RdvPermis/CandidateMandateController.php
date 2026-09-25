<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\CandidateMandateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CandidateMandateController extends Controller
{
    public function search(Request $request, CandidateMandateService $candidates): JsonResponse
    {
        $validated = $request->validate([
            'filtre' => ['nullable', 'array'],
            'filtre.groupePermis' => ['required_with:filtre', 'string', 'in:A,B,CE'],
            'filtre.nom' => ['nullable', 'array'],
            'filtre.nom.query' => ['nullable', 'string'],
            'filtre.nom.match' => ['nullable', 'string', 'in:PARTIAL,EXACT'],
            'filtre.numeroDossier' => ['nullable', 'array'],
            'filtre.numeroDossier.query' => ['nullable', 'string'],
            'filtre.numeroDossier.match' => ['nullable', 'string', 'in:PARTIAL,EXACT'],
            'page' => ['nullable', 'integer', 'min:1'],
            'parPage' => ['nullable', 'integer', 'between:1,100'],
        ]);

        try {
            $response = $candidates->search($request->user(), $validated);

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
