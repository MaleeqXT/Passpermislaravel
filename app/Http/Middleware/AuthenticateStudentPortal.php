<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStudentPortal
{
    /**
     * A staff preview and the staff's normal web session share localhost.
     * Sanctum resolves that web session before its Authorization header, which
     * made the student portal intermittently act as the administrator. For
     * portal endpoints, an explicit valid Bearer token is authoritative; a
     * normal student session remains the fallback when no token is supplied.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->bearerToken()
            ? $this->userFromBearerToken($request)
            : Auth::guard('web')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function userFromBearerToken(Request $request): mixed
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return null;
        }

        return $accessToken->tokenable?->withAccessToken($accessToken);
    }
}
