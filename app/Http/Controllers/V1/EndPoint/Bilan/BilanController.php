<?php

namespace App\Http\Controllers\V1\EndPoint\Bilan;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Models\StudentBilan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BilanController extends Controller
{
    public function students(Request $request): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['data' => []]);
        }

        $students = Student::query()
            ->whereHas('trainings.reservation', fn ($query) => $query->where('monitor_id', $monitor->id))
            ->with('user:id,name,first_name,last_name')
            ->get()
            ->sortBy(fn (Student $student) => mb_strtolower($this->studentName($student)))
            ->values()
            ->map(fn (Student $student) => [
                'id' => (string) $student->id,
                'name' => $this->studentName($student),
            ]);

        return response()->json(['data' => $students]);
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['message' => 'Moniteur introuvable.'], 404);
        }

        $this->ensureStudentBelongsToMonitor($student, $monitor);

        return response()->json(['data' => $this->payloadFor($student, null, $monitor)]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['message' => 'Moniteur introuvable.'], 404);
        }

        $this->ensureStudentBelongsToMonitor($student, $monitor);

        $validated = $request->validate([
            'evaluation_date' => ['nullable', 'date'],
            'observed_competencies' => ['nullable', 'array'],
            'observed_competencies.*' => ['integer', 'between:1,4'],
            'competencies' => ['required', 'array'],
            'competencies.*' => ['required', 'array'],
            'competencies.*.rating' => ['nullable', 'string', 'in:pas_vu,faible,moyen,bon'],
            'competencies.*.observations' => ['present', 'array', 'max:12'],
            'competencies.*.observations.*' => ['required', 'string', 'max:100'],
            'monitor_agreement' => ['nullable', 'string', 'in:oui,non'],
        ]);

        $existing = StudentBilan::query()->where('student_id', $student->id)->first();
        $existingEvaluation = is_array($existing?->evaluations) ? $existing->evaluations : [];
        $evaluation = [
            'evaluation_date' => $validated['evaluation_date'] ?? null,
            'observed_competencies' => array_values(array_unique($validated['observed_competencies'] ?? [])),
            'competencies' => $validated['competencies'],
            // There is no existing student approval endpoint or signature
            // workflow. Keep an already-recorded value instead of letting a
            // monitor impersonate the student's agreement.
            'student_agreement' => $existingEvaluation['student_agreement'] ?? null,
            'decision_history' => $existingEvaluation['decision_history'] ?? [],
            'monitor_agreement' => $validated['monitor_agreement'] ?? null,
        ];

        $bilan = StudentBilan::query()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'monitor_id' => $monitor->id,
                'evaluations' => $evaluation,
                'hours' => ['total_minutes' => $this->lessonMinutes($student)],
                // Saving from the monitor page is the existing publication
                // action. Share once; a finalized student decision is never
                // silently reset by a later monitor edit.
                'shared_at' => $existing?->shared_at ?? now(),
            ],
        );

        return response()->json(['data' => $this->payloadFor($student, $bilan, $monitor)]);
    }

    public function showForStudent(Request $request): JsonResponse
    {
        $student = $this->resolveStudentPortal($request);

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        $payload = $this->payloadFor($student);
        // A staff fallback is read-only.  A real student (including the
        // short-lived student preview token) still receives the normal
        // signature/decision workflow.
        if (!$request->user()?->student) {
            $payload['can_student_decide'] = false;
        }

        return response()->json(['data' => $payload]);
    }

    public function decide(Request $request): JsonResponse
    {
        $student = $request->user()?->student;

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:accepted,rejected'],
            'signature' => ['required_if:decision,accepted', 'nullable', 'array', 'max:50'],
            'signature.*' => ['required', 'array', 'min:2', 'max:500'],
            'signature.*.*.x' => ['required', 'numeric', 'between:0,600'],
            'signature.*.*.y' => ['required', 'numeric', 'between:0,180'],
        ]);

        $bilan = DB::transaction(function () use ($student, $validated) {
            $bilan = StudentBilan::query()
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            abort_unless($bilan && $bilan->shared_at, 422, 'Ce bilan n’est pas encore disponible pour votre décision.');

            // The student may change a saved decision. Each previous decision
            // is kept in the history so the monitor can see what changed.
            $evaluation = is_array($bilan->evaluations) ? $bilan->evaluations : [];
            if (in_array($bilan->decision_status, ['accepted', 'rejected'], true)) {
                $history = is_array($evaluation['decision_history'] ?? null) ? $evaluation['decision_history'] : [];
                $history[] = ['decision' => $bilan->decision_status, 'decided_at' => $bilan->decision_at?->toISOString()];
                $evaluation['decision_history'] = array_slice($history, -20);
            }
            $evaluation['student_agreement'] = $validated['decision'] === 'accepted' ? 'oui' : 'non';
            $bilan->forceFill([
                'decision_status' => $validated['decision'],
                'decision_at' => now(),
                'student_signature' => $validated['decision'] === 'accepted' ? $validated['signature'] : null,
                'evaluations' => $evaluation,
            ])->save();

            return $bilan->refresh();
        });

        return response()->json(['data' => $this->payloadFor($student, $bilan)]);
    }

    private function resolveMonitor(Request $request): ?Monitor
    {
        $user = $request->user();
        $monitor = $user?->monitor ?? Monitor::query()->where('user_id', $user?->id)->first();

        if (!$monitor && $request->filled('monitor_id') && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $monitor = Monitor::query()
                ->whereKey($request->input('monitor_id'))
                ->orWhere('user_id', $request->input('monitor_id'))
                ->first();
        }

        return $monitor;
    }

    /**
     * Resolve the selected student when an authorised staff member opens the
     * student dashboard preview with an existing staff session.  The normal
     * path always remains the authenticated student's own profile.
     */
    private function resolveStudentPortal(Request $request): ?Student
    {
        $user = $request->user();
        if ($user?->student) {
            return $user->student->loadMissing('user');
        }

        $studentId = $request->string('student_id')->toString();
        // The application stores its highest-privilege staff users with the
        // `admin` role (the UI labels that role “Super Admin”).  Passing a
        // non-existent Spatie role name here makes hasAnyRole() throw before
        // the selected student's bilan can be loaded during Connecter.
        if (!$studentId || !$user?->hasAnyRole(['admin', 'secretary'])) {
            return null;
        }

        $student = Student::query()->with('user')->find($studentId);
        if (!$student) {
            return null;
        }

        if ($user->hasRole('secretary')
            && $user->zone_id
            && $student->user?->zone_id
            && $user->zone_id !== $student->user->zone_id) {
            return null;
        }

        return $student;
    }

    private function ensureStudentBelongsToMonitor(Student $student, Monitor $monitor): void
    {
        $isAssigned = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->exists();

        abort_unless($isAssigned, 403, 'Ce candidat n’est pas lié à ce moniteur.');
    }

    private function payloadFor(Student $student, ?StudentBilan $bilan = null, ?Monitor $viewer = null): array
    {
        $bilan ??= StudentBilan::query()->with('monitor.user')->where('student_id', $student->id)->first();
        $evaluation = is_array($bilan?->evaluations) ? $bilan->evaluations : [];
        $minutes = $this->lessonMinutes($student);

        return [
            'id' => $bilan?->id,
            'student_id' => (string) $student->id,
            'monitor_id' => $bilan?->monitor_id ?? $viewer?->id,
            'student_name' => $this->studentName($student),
            'monitor_name' => $bilan?->monitor?->user?->name ?? $viewer?->user?->name ?? '—',
            'evaluation' => $evaluation,
            // Keep this legacy key while clients transition to `evaluation`.
            'evaluations' => $evaluation,
            'total_hours' => $this->formatHours($minutes),
            'hours' => ['total_minutes' => $minutes],
            'shared_at' => $bilan?->shared_at?->toISOString(),
            'decision_status' => $bilan?->decision_status ?? 'pending',
            'decision_at' => $bilan?->decision_at?->toISOString(),
            'student_signature' => $bilan?->student_signature,
            // Shared bilans stay open: the student may accept, refuse or change
            // a previous decision.
            'can_student_decide' => (bool) $bilan?->shared_at,
            'decision_history' => is_array($evaluation['decision_history'] ?? null) ? $evaluation['decision_history'] : [],
            'updated_at' => $bilan?->updated_at?->toISOString(),
        ];
    }

    /**
     * Driving time actually done by the student (all monitors): lessons that
     * have ended, were not cancelled and where the student was present.
     * Uses the real lesson length (duration_minutes, else `hour`), the same
     * rules as the "Heures effectuées" counter of the monitor's student fiche.
     */
    private function lessonMinutes(Student $student): int
    {
        $now = now();

        return (int) Reservation::query()
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->with(['training.cancellation', 'reviewMonitor', 'evaluation'])
            ->get()
            ->sum(function (Reservation $lesson) use ($now) {
                $cancelled = ! $lesson->is_active
                    || (int) $lesson->training?->cancellation?->status === CancellationStatusEnum::SUCCESS_CANCELLED->value;
                if ($cancelled) {
                    return 0;
                }

                $end = Carbon::parse($lesson->date->toDateString().' '.$lesson->end_at->format('H:i'));
                if ($end->greaterThan($now)) {
                    return 0;
                }

                $data = $lesson->evaluation?->data ?? [];
                $absent = (bool) ($data['formation']['is_absent'] ?? $lesson->reviewMonitor?->is_absent ?? false);
                if ($absent) {
                    return 0;
                }

                return $lesson->duration_minutes === null
                    ? max(0, (int) $lesson->hour * 60)
                    : max(0, (int) $lesson->duration_minutes);
            });
    }

    private function formatHours(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining ? $hours.'h'.str_pad((string) $remaining, 2, '0', STR_PAD_LEFT) : (string) $hours;
    }

    private function studentName(Student $student): string
    {
        return trim(implode(' ', array_filter([
            $student->user?->first_name,
            $student->user?->last_name,
        ]))) ?: ($student->user?->name ?? 'Élève');
    }
}
