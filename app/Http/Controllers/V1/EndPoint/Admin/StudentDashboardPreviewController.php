<?php

namespace App\Http\Controllers\V1\EndPoint\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardPreviewController extends Controller
{
    /**
     * Issue a short-lived, student-scoped token for the staff "Connecter"
     * action.  The normal staff token is kept only in the browser session and
     * is restored when the preview is closed.
     */
    public function start(Request $request, Student $student): JsonResponse
    {
        $student->loadMissing('user');

        abort_unless($student->user, 404, 'Compte élève introuvable.');

        // Secretaries are limited to their own agency.  Admins retain the
        // existing application-wide access granted by the admin candidates
        // area.
        $actor = $request->user();
        if ($actor?->hasRole('secretary')
            && $actor->zone_id
            && $student->user->zone_id
            && $actor->zone_id !== $student->user->zone_id) {
            abort(403, 'Cet élève n’appartient pas à votre agence.');
        }

        $expiresAt = now()->addMinutes(30);
        $accessToken = $student->user->createToken(
            'student-dashboard-preview',
            ['student-dashboard:preview'],
            $expiresAt,
        );

        return response()->json([
            'data' => [
                'token' => $accessToken->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'student_id' => $student->id,
                'user' => [
                    'id' => $student->user->id,
                    'name' => $student->user->name,
                    'first_name' => $student->user->first_name,
                    'last_name' => $student->user->last_name,
                    'email' => $student->user->email,
                ],
            ],
        ]);
    }

    /** Revoke the token issued for the current staff preview. */
    public function stop(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        abort_unless(
            $accessToken && $accessToken->can('student-dashboard:preview'),
            403,
            'Cette session ne peut pas être fermée par cette route.',
        );

        $accessToken->delete();

        return response()->json(status: 204);
    }
}
