<?php

namespace App\Http\Controllers\V1\EndPoint\Bilan;

use App\Http\Controllers\Controller;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Models\StudentBilan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['data' => $this->payloadFor($student)]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->resolveMonitor($request);

        if (!$monitor) {
            return response()->json(['message' => 'Moniteur introuvable.'], 404);
        }

        $this->ensureStudentBelongsToMonitor($student, $monitor);

        $validated = $request->validate([
            'evaluations' => ['required', 'array'],
            'evaluations.*' => ['required', 'string', 'in:empty,orange,green'],
            'hours' => ['required', 'array'],
            'hours.bilan1' => ['required', 'numeric', 'min:0', 'max:10000'],
            'hours.bilan2' => ['required', 'numeric', 'min:0', 'max:10000'],
        ]);

        $bilan = StudentBilan::query()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'monitor_id' => $monitor->id,
                'evaluations' => $validated['evaluations'],
                'hours' => $validated['hours'],
            ],
        );

        return response()->json(['data' => $this->payloadFor($student, $bilan)]);
    }

    public function showForStudent(Request $request): JsonResponse
    {
        $student = $request->user()?->student;

        if (!$student) {
            return response()->json(['message' => 'Élève introuvable.'], 404);
        }

        return response()->json(['data' => $this->payloadFor($student)]);
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

    private function ensureStudentBelongsToMonitor(Student $student, Monitor $monitor): void
    {
        $isAssigned = Reservation::query()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', fn ($query) => $query->where('student_id', $student->id))
            ->exists();

        abort_unless($isAssigned, 403, 'Ce candidat n’est pas lié à ce moniteur.');
    }

    private function payloadFor(Student $student, ?StudentBilan $bilan = null): array
    {
        $bilan ??= StudentBilan::query()->where('student_id', $student->id)->first();
        $hours = $bilan?->hours ?? [];

        return [
            'evaluations' => $bilan?->evaluations ?? [],
            'hours' => [
                'bilan1' => (string) ($hours['bilan1'] ?? '0'),
                'bilan2' => (string) ($hours['bilan2'] ?? '0'),
            ],
            'updated_at' => $bilan?->updated_at?->toISOString(),
        ];
    }

    private function studentName(Student $student): string
    {
        return trim(implode(' ', array_filter([
            $student->user?->first_name,
            $student->user?->last_name,
        ]))) ?: ($student->user?->name ?? 'Élève');
    }
}
