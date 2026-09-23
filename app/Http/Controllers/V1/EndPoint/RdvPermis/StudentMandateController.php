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
        $validated = $request->validate([
            // The confirmed contract accepts the group value. Do not impose an unconfirmed enum locally.
            'groupe_permis' => ['bail', 'required', 'string'],
        ]);

        try {
            $response = $mandates->synchronize($request->user(), $student, $validated['groupe_permis']);

            return response()->json($response->json(), $response->status());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
