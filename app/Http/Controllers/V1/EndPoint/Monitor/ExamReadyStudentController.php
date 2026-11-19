<?php

namespace App\Http\Controllers\V1\EndPoint\Monitor;

use App\Http\Controllers\Controller;
use App\Models\MonitorExamReadiness;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Services\Monitor\Student\MonitorExamReadinessService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Monitor-facing management of "élèves prêts pour un examen".
 *
 * This controller does not own any new storage: it reuses the existing
 * monitor_exam_readinesses table (one row per student, unique student_id) and
 * MonitorExamReadinessService, which already handles the ready flag, ready_at,
 * the responsible monitor and the secretariat notification. It never creates
 * an official exam booking and never calls RdvPermis.
 */
class ExamReadyStudentController extends Controller
{
    public function __construct(private readonly MonitorExamReadinessService $readiness) {}

    /** Paginated table of readiness records for this monitor's own students. */
    public function index(Request $request): JsonResponse
    {
        $monitor = $this->monitor($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,ready,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? 'all';

        $page = MonitorExamReadiness::query()
            ->with(['student.user:id,name,first_name,last_name,phone', 'monitor.user:id,name,first_name,last_name'])
            ->whereHas('student', fn ($student) => $this->linkedTo($student, $monitor))
            ->when($status === 'ready', fn ($query) => $query->where('is_ready', true))
            ->when($status === 'cancelled', fn ($query) => $query->where('is_ready', false))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->whereHas('student.user', fn ($user) => $user
                    ->where('name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like));
            })
            ->orderByDesc('is_ready')
            ->orderByDesc('updated_at')
            ->paginate((int) ($filters['per_page'] ?? 10));

        return response()->json([
            'data' => $page->getCollection()->map(fn (MonitorExamReadiness $row) => $this->serialize($row))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** Students this monitor may still register (linked and not already active). */
    public function candidates(Request $request): JsonResponse
    {
        $monitor = $this->monitor($request);

        $students = Student::query()
            ->with('user:id,name,first_name,last_name,phone')
            ->tap(fn ($query) => $this->linkedTo($query, $monitor))
            ->whereNotIn('id', MonitorExamReadiness::query()->where('is_ready', true)->select('student_id'))
            ->get()
            ->map(fn (Student $student) => [
                'id' => (string) $student->id,
                'name' => $this->studentName($student),
                'neph' => $student->neph ?: null,
                'phone' => $student->user?->phone ?: null,
            ])
            ->sortBy(fn (array $row) => mb_strtolower($row['name']))
            ->values();

        return response()->json(['data' => $students]);
    }

    public function store(Request $request): JsonResponse
    {
        $monitor = $this->monitor($request);

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'exists:students,id'],
            ...$this->detailRules(),
        ], $this->messages());

        $student = Student::query()->findOrFail($validated['student_id']);
        $this->authorizeStudent($monitor, $student);

        $existing = MonitorExamReadiness::query()->where('student_id', $student->id)->first();
        if ($existing?->is_ready) {
            $this->duplicate();
        }

        try {
            $this->readiness->update($monitor, $student, $this->attributes($validated, true));
        } catch (QueryException $e) {
            // Two simultaneous registrations hit the unique student_id index.
            if (($e->errorInfo[0] ?? null) === '23000') {
                $this->duplicate();
            }
            throw $e;
        }

        return response()->json([
            'message' => 'L’élève a été enregistré comme prêt pour un examen.',
            'data' => $this->serialize($this->fresh($student)),
        ], 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);

        $existing = MonitorExamReadiness::query()->where('student_id', $student->id)->first();
        if (! $existing?->is_ready) {
            throw ValidationException::withMessages([
                'student_id' => 'Cet élève n’a pas d’enregistrement actif à modifier.',
            ]);
        }

        $validated = $request->validate($this->detailRules(), $this->messages());
        $this->readiness->update($monitor, $student, $this->attributes($validated, true));

        return response()->json([
            'message' => 'Les informations ont été mises à jour.',
            'data' => $this->serialize($this->fresh($student)),
        ]);
    }

    /** Cancels the readiness (is_ready = false). The record is kept for history. */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        $monitor = $this->monitor($request);
        $this->authorizeStudent($monitor, $student);

        $existing = MonitorExamReadiness::query()->where('student_id', $student->id)->first();
        if (! $existing?->is_ready) {
            throw ValidationException::withMessages([
                'student_id' => 'Cet enregistrement est déjà annulé.',
            ]);
        }

        $this->readiness->update($monitor, $student, [
            'is_ready' => false,
            'additional_hours' => $existing->additional_hours,
            'practice_frequency' => $existing->practice_frequency,
            'observation' => $existing->observation,
        ]);

        return response()->json([
            'message' => 'Le statut « Prêt pour un examen » a été annulé.',
            'data' => $this->serialize($this->fresh($student)),
        ]);
    }

    /** @return array<string, array<int, string>> */
    private function detailRules(): array
    {
        return [
            'additional_hours' => ['nullable', 'integer', 'min:1', 'max:100'],
            'practice_frequency' => ['nullable', 'string', 'max:120'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'student_id.required' => 'Veuillez sélectionner un élève.',
            'student_id.exists' => 'Cet élève est introuvable.',
            'additional_hours.integer' => 'Les heures complémentaires doivent être un nombre entier.',
            'additional_hours.min' => 'Les heures complémentaires doivent être comprises entre 1 et 100.',
            'additional_hours.max' => 'Les heures complémentaires doivent être comprises entre 1 et 100.',
            'practice_frequency.max' => 'La fréquence ne doit pas dépasser 120 caractères.',
            'observation.max' => 'Les remarques ne doivent pas dépasser 2000 caractères.',
        ];
    }

    /** @return array{is_ready: bool, additional_hours: ?int, practice_frequency: ?string, observation: ?string} */
    private function attributes(array $validated, bool $ready): array
    {
        $clean = fn (?string $value) => filled($value) ? trim($value) : null;

        return [
            'is_ready' => $ready,
            'additional_hours' => $validated['additional_hours'] ?? null,
            'practice_frequency' => $clean($validated['practice_frequency'] ?? null),
            'observation' => $clean($validated['observation'] ?? null),
        ];
    }

    private function duplicate(): never
    {
        throw ValidationException::withMessages([
            'student_id' => 'Cet élève est déjà enregistré comme prêt pour un examen.',
        ]);
    }

    private function fresh(Student $student): MonitorExamReadiness
    {
        return MonitorExamReadiness::query()
            ->with(['student.user:id,name,first_name,last_name,phone', 'monitor.user:id,name,first_name,last_name'])
            ->where('student_id', $student->id)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function serialize(MonitorExamReadiness $row): array
    {
        $student = $row->student;

        return [
            'id' => $row->id,
            'student_id' => (string) $row->student_id,
            'candidate' => $student ? $this->studentName($student) : 'Élève',
            'neph' => $student?->neph ?: null,
            'phone' => $student?->user?->phone ?: null,
            'monitor' => $this->personName($row->monitor?->user) ?: null,
            'monitor_id' => $row->monitor_id,
            'is_ready' => (bool) $row->is_ready,
            'status' => $row->is_ready ? 'ready' : 'cancelled',
            'status_label' => $row->is_ready ? 'Prêt pour un examen' : 'Annulé',
            'registered_at' => ($row->ready_at ?? $row->created_at)?->toIso8601String(),
            'updated_at' => $row->updated_at?->toIso8601String(),
            'additional_hours' => $row->additional_hours,
            'practice_frequency' => $row->practice_frequency,
            'observation' => $row->observation,
        ];
    }

    /** Restricts a Student query to candidates with a lesson booked with this monitor. */
    private function linkedTo($query, Monitor $monitor)
    {
        return $query->whereHas('trainings.reservation', fn ($reservation) => $reservation->where('monitor_id', $monitor->id));
    }

    private function authorizeStudent(Monitor $monitor, Student $student): void
    {
        abort_unless(
            $this->linkedTo(Student::query()->whereKey($student->id), $monitor)->exists(),
            403,
            'Ce candidat n’est pas lié à ce moniteur.'
        );
    }

    /**
     * Same resolution rule as BilanController/FormationController: a monitor
     * acts as themselves; staff may act for a monitor via monitor_id. Anyone
     * else (students, for example) is refused.
     */
    private function monitor(Request $request): Monitor
    {
        $user = $request->user();
        $monitor = $user?->monitor ?? Monitor::query()->where('user_id', $user?->id)->first();

        if (! $monitor && $request->filled('monitor_id') && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $monitor = Monitor::query()
                ->whereKey($request->input('monitor_id'))
                ->orWhere('user_id', $request->input('monitor_id'))
                ->first();
        }

        abort_unless($monitor, 403, 'Compte moniteur requis.');

        return $monitor;
    }

    private function studentName(Student $student): string
    {
        return $this->personName($student->user) ?: 'Élève';
    }

    private function personName(mixed $user): string
    {
        $full = trim(implode(' ', array_filter([$user?->first_name, $user?->last_name])));

        return $full ?: (string) ($user?->name ?? '');
    }
}
