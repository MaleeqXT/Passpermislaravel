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

    public function search(Request $request, CandidateEligibilityService $candidates): JsonResponse
    {
        // Swagger currently confirms the path only. Preserve a schema-neutral
        // passthrough boundary; the UI does not submit a search until the
        // request contract is captured.
        $criteria = $request->json()->all();

        try {
            $response = $candidates->searchHeavyVehicle($request->user(), $criteria);

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return $this->providerError($exception);
        } catch (Throwable $exception) {
            return $this->internalError($exception);
        }
    }
}
