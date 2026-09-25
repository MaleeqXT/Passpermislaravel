<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\PlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PlanningController extends Controller
{
    public function search(Request $request, PlanningService $planning): JsonResponse
    {
        $validated = $request->validate([
            'groupePermis' => ['bail', 'required', 'string', 'in:A,B,CE'],
            'date' => ['bail', 'required', 'date_format:Y-m-d'],
            'centreId' => ['bail', 'required', 'uuid'],
        ]);

        try {
            $response = $planning->search(
                $request->user(),
                $validated['groupePermis'],
                $validated['date'],
                $validated['centreId'],
            );

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
