<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RdvPermis\CandidateEligibilityRequest;
use App\Services\RdvPermis\CandidateEligibilityService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CandidateEligibilityController extends Controller
{
    use HandlesRdvPermisErrors;

    public function search(CandidateEligibilityRequest $request, CandidateEligibilityService $candidates): JsonResponse
    {
        try {
            $response = $candidates->search($request->user(), $request->validated());

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return $this->providerError($exception);
        } catch (Throwable $exception) {
            return $this->internalError($exception);
        }
    }
}
