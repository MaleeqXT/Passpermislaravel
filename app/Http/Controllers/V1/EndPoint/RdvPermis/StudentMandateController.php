<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Services\RdvPermis\StudentMandateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StudentMandateController extends Controller
{
    use HandlesRdvPermisErrors;

    /**
     * Local-development preview of the exact body store() would send. It calls
     * no provider endpoint and needs no token. It does not exist outside the
     * local and testing environments.
     */
    public function preview(Student $student, StudentMandateService $mandates): JsonResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        try {
            return response()->json(['payload' => $mandates->payloadFor($student)]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request, Student $student, StudentMandateService $mandates): JsonResponse
    {
        try {
            $response = $mandates->synchronize($request->user(), $student);

            return response()->json($response->json(), $response->status());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RdvPermisApiException $exception) {
            $body = ['message' => $exception->userMessage];

            if (app()->environment('staging')) {
                $body['rdvpermis_status'] = $exception->rdvPermisStatus ?? $exception->responseStatus;

                if ($exception->rdvPermisError !== null) {
                    $body['rdvpermis_error'] = $exception->rdvPermisError;
                }
            }

            return $this->providerError($exception, array_diff_key($body, ['message' => true]));
        } catch (\Throwable $exception) {
            return $this->internalError($exception);
        }
    }
}
