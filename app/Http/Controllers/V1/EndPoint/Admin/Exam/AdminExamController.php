<?php

namespace App\Http\Controllers\V1\EndPoint\Admin\Exam;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Student\Examen\ExamenResultPermisEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Exam\StudentExam;
use App\Models\Roles\Student\User\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminExamController extends Controller
{
    /** List active students together with the exam appointment managed by staff. */
    public function index(Request $request): JsonResponse
    {
        $zoneId = $this->effectiveZoneId($request);
        $students = $this->studentsQuery($zoneId)->get();

        return response()->json([
            'data' => $students->map(fn (Student $student) => $this->serializeStudent($student))->values(),
            'filters' => [
                'monitors' => $this->monitorOptions($zoneId),
                'locations' => $this->locationOptions($zoneId),
                'students' => $students
                    ->map(function (Student $student) {
                        $exam = $this->serializeStudent($student);

                        return ['value' => $exam['id'], 'label' => $exam['candidate']];
                    })
                    ->sortBy('label')
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * Create or update the pending exam appointment for one student.
     * The student dashboard reads this same StudentExam record.
     */
    public function update(Request $request, Student $student): JsonResponse
    {
        $zoneId = $this->effectiveZoneId($request);
        // An agency user may only modify candidates from their own agency. A
        // Super Admin (who has no assigned zone) can explicitly select one.
        $student = $this->studentsQuery($zoneId)->findOrFail($student->id);

        $validated = $request->validate([
            'date_examen' => ['sometimes', 'nullable', 'date'],
            'heure_passage' => ['sometimes', 'nullable', 'date_format:H:i'],
            'comment' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['on_hold', 'successful', 'failed'])],
            'result_permis' => ['sometimes', 'nullable', Rule::in(['accepted', 'refusal'])],
        ]);

        $exam = StudentExam::query()
            ->where('student_id', $student->id)
            ->where('status', ExamenStatusEnum::PENDING->value)
            ->whereNull('result_permis')
            ->orderBy('date_examen')
            ->first();

        if (! $exam) {
            $exam = new StudentExam([
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'is_auto' => str_contains(strtolower((string) $student->boite_type), 'auto'),
                'status' => ExamenStatusEnum::PENDING->value,
            ]);
        }

        foreach (['date_examen', 'heure_passage', 'comment'] as $field) {
            if (array_key_exists($field, $validated)) {
                $exam->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('status', $validated)) {
            $exam->status = $this->databaseStatus($validated['status']);
        }

        if (array_key_exists('result_permis', $validated)) {
            $exam->result_permis = $this->databaseResult($validated['result_permis']);
        }

        $exam->save();

        // StudentExam::saved centrally evaluates a confirmed official date,
        // including this API and the legacy administration edit workflow.

        $student = $this->studentsQuery($zoneId)->findOrFail($student->id);

        return response()->json([
            'message' => 'Examen mis à jour avec succès.',
            'data' => $this->serializeStudent($student),
        ]);
    }

    private function studentsQuery(?string $zoneId = null)
    {
        return Student::query()
            ->with([
                'user:id,name,first_name,last_name,phone',
                'preferredMonitor.user:id,name,first_name,last_name',
                'exams.monitor.user:id,name,first_name,last_name',
                'exams.lieu:id,name',
            ])
            ->whereHas('user', fn ($query) => $query->where('status', SituationStatusEnum::ACTIVE->value))
            ->when($zoneId, fn ($query) => $query->whereHas('user', fn ($user) => $user->where('zone_id', $zoneId)))
            ->orderByDesc('created_at');
    }

    private function effectiveZoneId(Request $request): ?string
    {
        return $request->user()?->zone_id ?: $request->input('zone_id');
    }

    private function monitorOptions(?string $zoneId): array
    {
        return Monitor::query()
            ->with('user:id,name,first_name,last_name')
            ->whereIn('status', [1, '1', 'active'])
            // Older monitor accounts have no zone yet. They must remain
            // selectable until they are assigned, otherwise the agency list
            // incorrectly appears to contain only newly-created monitors.
            ->when($zoneId, fn ($query) => $query->whereHas('user', fn ($user) => $user
                ->where('zone_id', $zoneId)
                ->orWhereNull('zone_id')))
            ->get()
            ->map(fn (Monitor $monitor) => [
                'value' => $monitor->id,
                'label' => $this->personName($monitor->user),
            ])
            ->filter(fn (array $monitor) => filled($monitor['label']))
            ->unique()
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function locationOptions(?string $zoneId): array
    {
        return Lieu::query()
            ->when($zoneId, fn ($query) => $query->where('zone_id', $zoneId))
            ->where('status', true)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    private function serializeStudent(Student $student): array
    {
        $pendingExam = $student->exams
            ->filter(fn (StudentExam $exam) => (int) $exam->status === ExamenStatusEnum::PENDING->value && blank($exam->result_permis))
            ->sortBy(fn (StudentExam $exam) => $exam->date_examen ?: '9999-12-31')
            ->first();

        $exam = $pendingExam ?: $student->exams
            ->sortByDesc(fn (StudentExam $item) => ($item->date_examen ?: '').' '.($item->created_at ?: ''))
            ->first();

        $examMonitor = $exam?->monitor?->user;
        $preferredMonitor = $student->preferredMonitor?->user;
        $user = $student->user;
        $fullName = trim(implode(' ', array_filter([$user?->first_name, $user?->last_name])));
        $monitorName = $this->personName($examMonitor) ?: $this->personName($preferredMonitor);

        return [
            // This is the Student id: it guarantees that an admin edit belongs
            // to the same student account that calls /api/student/exams.
            'id' => $student->id,
            'candidate' => $fullName ?: ($user?->name ?: 'Élève'),
            'phone' => $user?->phone ?: '',
            'box' => str_contains(strtolower((string) $student->boite_type), 'auto') ? 'Auto' : 'Manuel',
            'examDate' => $exam?->date_examen ? substr((string) $exam->date_examen, 0, 10) : '',
            'startTime' => $this->startTime($exam),
            'status' => $this->frontendStatus($exam?->status),
            'resultPermis' => $this->frontendResult($exam?->result_permis),
            'comment' => $exam?->comment ?: '',
            'monitor' => $monitorName,
            'monitorId' => $exam?->monitor?->id ?: $student->preferredMonitor?->id,
            'location' => $exam?->lieu?->name ?: '',
            'attemptCount' => $student->exams->filter(fn (StudentExam $item) =>
                ! blank($item->result_permis)
                || in_array((int) $item->status, [ExamenStatusEnum::SUCCESS->value, ExamenStatusEnum::FAILED->value], true)
            )->count(),
        ];
    }

    private function startTime(?StudentExam $exam): ?array
    {
        if (! $exam?->heure_passage) {
            return null;
        }

        $time = $exam->heure_passage->format('H:i');

        return [
            'hour' => (int) substr($time, 0, 2),
            'minute' => (int) substr($time, 3, 2),
        ];
    }

    private function personName(mixed $user): string
    {
        return $user?->name ?: trim(implode(' ', array_filter([$user?->first_name, $user?->last_name])));
    }

    private function databaseStatus(string $status): int
    {
        return match ($status) {
            'successful' => ExamenStatusEnum::SUCCESS->value,
            'failed' => ExamenStatusEnum::FAILED->value,
            default => ExamenStatusEnum::PENDING->value,
        };
    }

    private function databaseResult(?string $result): ?int
    {
        return match ($result) {
            'accepted' => ExamenResultPermisEnum::ADMIS->value,
            'refusal' => ExamenResultPermisEnum::REFUS->value,
            default => null,
        };
    }

    private function frontendStatus(mixed $status): string
    {
        return match ((int) $status) {
            ExamenStatusEnum::SUCCESS->value => 'successful',
            ExamenStatusEnum::FAILED->value => 'failed',
            default => 'on_hold',
        };
    }

    private function frontendResult(mixed $result): string
    {
        return match ((int) $result) {
            ExamenResultPermisEnum::ADMIS->value => 'accepted',
            ExamenResultPermisEnum::REFUS->value => 'refusal',
            default => '',
        };
    }
}
