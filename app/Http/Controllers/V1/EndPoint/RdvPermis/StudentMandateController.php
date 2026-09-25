<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Services\RdvPermis\StudentMandateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class StudentMandateController extends Controller
{
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

            return response()->json($body, $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
