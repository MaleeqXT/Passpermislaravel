<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesRdvPermisErrors
{
    private function providerError(RdvPermisApiException $exception, array $extra = []): JsonResponse
    {
        $body = ['message' => $exception->userMessage, ...$extra];
        $headers = [];
        if ($exception->retryAfter !== null) {
            $body['retry_after'] = $exception->retryAfter;
            $headers['Retry-After'] = (string) $exception->retryAfter;
        }

        return response()->json($body, $exception->responseStatus, $headers);
    }

    private function internalError(Throwable $exception): JsonResponse
    {
        Log::error('RdvPermis internal operation failed', ['exception' => $exception::class]);

        return response()->json(['message' => 'La demande RdvPermis n’a pas pu être traitée. Réessayez plus tard.'], 500);
    }
}
