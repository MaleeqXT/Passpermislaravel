<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreferBearerTokenAuthentication
{
    /**
     * Sanctum normally resolves the stateful web-session user before it looks
     * at an Authorization header. An admin opening a student preview therefore
     * remained authenticated as the admin because both credentials exist on
     * localhost. An explicit Bearer token must take precedence for this
     * request, while ordinary cookie-based sessions retain their normal flow.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken()) {
            return $next($request);
        }

        $originalGuards = config('sanctum.guard');
        config(['sanctum.guard' => []]);

        try {
            return $next($request);
        } finally {
            config(['sanctum.guard' => $originalGuards]);
        }
    }
}
