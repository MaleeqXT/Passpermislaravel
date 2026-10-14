<?php

namespace App\Http\Controllers\Theory;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Models\Theory\TheoryManualCodeAccess;
use App\Services\Theory\TheoryCodeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Staff-only cash / in-agency access ledger for the Code module. */
class TheoryManualCodeAccessController extends Controller
{
    public function __construct(private readonly TheoryCodeAccessService $access)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'zone_id' => ['nullable', 'uuid', 'exists:zones,id'],
        ]);
        $staffZoneId = $request->user()?->zone_id;
        $zoneId = $data['zone_id'] ?? $staffZoneId;
        // The selected agency is persisted on the staff account by the shared
        // header. Do not let a request impersonate a different agency.
        abort_if(isset($data['zone_id']) && (string) $data['zone_id'] !== (string) $staffZoneId, 403);
        $term = trim((string) ($data['search'] ?? ''));

        $students = Student::query()
            ->with('user:id,first_name,last_name,name,email,zone_id')
            ->when($zoneId, fn ($query) => $query->whereHas('user', fn ($user) => $user->where('zone_id', $zoneId)))
            ->when($term !== '', function ($query) use ($term) {
                $query->whereHas('user', function ($user) use ($term) {
                    $like = '%'.$term.'%';
                    $user->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest()
            ->paginate($data['per_page'] ?? 20);

        $manualAccesses = TheoryManualCodeAccess::query()
            ->active()
            ->with('grantedBy:id,first_name,last_name,name')
            ->whereIn('student_id', $students->getCollection()->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $students->setCollection($students->getCollection()->map(function (Student $student) use ($manualAccesses) {
            $manualAccess = $manualAccesses->get($student->id);

            return $this->studentPayload(
                $student,
                $this->access->hasPaidPackageAccess($student),
                $manualAccess,
            );
        }));

        return response()->json($students);
    }

    public function grant(Request $request, Student $student): JsonResponse
    {
        $student = $this->visibleStudent($request, $student);
        $data = $request->validate([
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $attributes = [
            'granted_by_user_id' => $request->user()->id,
            'granted_at' => now(),
            'expires_at' => filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at'])->endOfDay() : null,
            'note' => filled($data['note'] ?? null) ? trim($data['note']) : 'Paiement en espèces confirmé par l’agence.',
        ];

        // There is one current manual grant per student. Re-granting refreshes
        // its date and note instead of producing several simultaneous grants.
        $manualAccess = TheoryManualCodeAccess::query()
            ->where('student_id', $student->id)
            ->whereNull('revoked_at')
            ->latest('granted_at')
            ->first();
        if ($manualAccess) {
            $manualAccess->update($attributes);
        } else {
            $manualAccess = TheoryManualCodeAccess::create([
                'student_id' => $student->id,
                ...$attributes,
            ]);
        }

        return response()->json([
            'message' => 'Accès Code activé pour cet élève.',
            'data' => $this->studentPayload($student, $this->access->hasPaidPackageAccess($student), $manualAccess->load('grantedBy:id,first_name,last_name,name')),
        ]);
    }

    public function revoke(Request $request, Student $student): JsonResponse
    {
        $student = $this->visibleStudent($request, $student);
        $manualAccess = TheoryManualCodeAccess::query()
            ->where('student_id', $student->id)
            ->whereNull('revoked_at')
            ->latest('granted_at')
            ->first();

        if (! $manualAccess) {
            return response()->json(['message' => 'Aucun accès manuel actif à retirer.'], 422);
        }

        $manualAccess->update([
            'revoked_at' => now(),
            'revoked_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Accès manuel Code retiré.',
            'data' => $this->studentPayload($student, $this->access->hasPaidPackageAccess($student), null),
        ]);
    }

    private function visibleStudent(Request $request, Student $student): Student
    {
        $student->loadMissing('user:id,first_name,last_name,name,email,zone_id');
        $staffZoneId = $request->user()?->zone_id;

        abort_if($staffZoneId && (string) $student->user?->zone_id !== (string) $staffZoneId, 403);

        return $student;
    }

    private function studentPayload(Student $student, bool $hasPaidCodeAccess, ?TheoryManualCodeAccess $manualAccess): array
    {
        $user = $student->user;

        return [
            'id' => $student->id,
            'name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: ($user?->name ?? 'Élève'),
            'email' => $user?->email,
            'has_paid_code_access' => $hasPaidCodeAccess,
            'has_manual_code_access' => $manualAccess !== null,
            'has_code_access' => $hasPaidCodeAccess || $manualAccess !== null,
            'manual_access' => $manualAccess ? [
                'id' => $manualAccess->id,
                'granted_at' => $manualAccess->granted_at?->toISOString(),
                'expires_at' => $manualAccess->expires_at?->toISOString(),
                'note' => $manualAccess->note,
                'granted_by' => trim(($manualAccess->grantedBy?->first_name ?? '').' '.($manualAccess->grantedBy?->last_name ?? '')) ?: $manualAccess->grantedBy?->name,
            ] : null,
        ];
    }
}
