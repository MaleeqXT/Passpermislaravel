<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedOrStudentPreview
{
    /**
     * A staff-issued student preview is deliberately short-lived and already
     * requires an authenticated staff member. It must be able to open a
     * student's dashboard even when that student's email has not been verified
     * yet. Normal student tokens still use Laravel's standard verification.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->tokenCan('student-dashboard:preview')) {
            return $next($request);
        }

        return app(EnsureEmailIsVerified::class)->handle($request, $next);
    }
}
