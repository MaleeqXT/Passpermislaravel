<?php

namespace App\Http\Middleware;

use App\Models\Roles\Monitor\User\Monitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveMonitorChat
{
    public function handle(Request $request, Closure $next)
    {
        $monitorId = $request->header('X-Chat-Monitor');
        if (!$monitorId) {
            return $next($request);
        }

        $staff = $request->user();
        // Same staff roles as the existing dashboard Connecter/impersonation flow.
        abort_unless($staff && (int) $staff->status === 1 && $staff->deleted_at === null
            && $staff->hasAnyRole(['admin', 'super-admin', 'secretary']), 403);
        $monitor = Monitor::findOrFail($monitorId);
        $actor = $monitor->user()->without(['student', 'monitor', 'secretary'])->firstOrFail();
        abort_unless((int) $actor->status === 1 && $actor->hasRole('monitor'), 403);

        $guard = Auth::guard('sanctum');
        $resolver = $request->getUserResolver();
        $request->attributes->set('chat_staff_id', $staff->id);
        $guard->setUser($actor);
        $request->setUserResolver(fn ($name = null) => $actor);
        try {
            return $next($request);
        } finally {
            // This context applies only to this chat request, never to the login session.
            $guard->setUser($staff);
            $request->setUserResolver($resolver);
        }
    }
}
