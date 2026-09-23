<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimulateSlowResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && filter_var(env('SIMULATE_SLOW_RESPONSE', false), FILTER_VALIDATE_BOOLEAN)) {
            sleep((int) env('REQUEST_SLOW', 1));
        }
        return $next($request);
    }
}
