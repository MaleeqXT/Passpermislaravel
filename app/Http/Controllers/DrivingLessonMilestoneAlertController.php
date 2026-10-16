<?php

namespace App\Http\Controllers;

use App\Models\DrivingLessonMilestoneAlert;
use App\Services\DrivingLessonMilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrivingLessonMilestoneAlertController extends Controller
{
    /** Show a pending milestone only to an admin or secretary dashboard. */
    public function pending(Request $request, DrivingLessonMilestoneService $milestones): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'super-admin', 'secretary']), 403, 'Cette alerte ne vous est pas accessible.');

        $requestedZoneId = $request->filled('zone_id') ? (string) $request->input('zone_id') : null;
        if (! $user->hasRole('super-admin') && $user->zone_id && $requestedZoneId && (string) $user->zone_id !== $requestedZoneId) {
            abort(403, 'Cette agence ne vous est pas accessible.');
        }

        $zoneId = $user->hasRole('super-admin') ? $requestedZoneId : ($user->zone_id ?: $requestedZoneId);
        $alert = $milestones->nextPendingForStaff($zoneId, ! $user->hasRole('super-admin') && (bool) $user->zone_id);

        return response()->json([
            'data' => $milestones->payload($alert),
        ]);
    }

    public function acknowledge(
        Request $request,
        DrivingLessonMilestoneAlert $milestoneAlert,
        DrivingLessonMilestoneService $milestones,
    ): JsonResponse {
        $milestoneAlert->loadMissing('student.user', 'reservation.monitor');
        abort_unless($this->mayAcknowledge($request, $milestoneAlert), 403, 'Cette alerte ne vous est pas accessible.');

        if (! $milestoneAlert->acknowledged_at) {
            $milestoneAlert->update([
                'acknowledged_at' => now(),
                'acknowledged_by_user_id' => $request->user()->id,
            ]);
        }

        return response()->json([
            'data' => $milestones->payload($milestoneAlert->fresh('student')),
        ]);
    }

    private function mayAcknowledge(Request $request, DrivingLessonMilestoneAlert $alert): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if (! $user->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        $studentZoneId = $alert->student?->user?->zone_id;

        return ! $user->zone_id || ! $studentZoneId || (string) $user->zone_id === (string) $studentZoneId;
    }
}
