<?php

namespace App\Http\Controllers\V1\EndPoint\LivretNumerique;

use App\Http\Controllers\Controller;
use App\Models\LivretLesson;
use App\Services\LivretNumerique\LessonService;
use App\Services\LivretNumerique\LivretSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => LivretLesson::query()->with(['student.user', 'contract'])->whereNull('archived_at')->latest('lesson_date')->paginate((int) $request->integer('perPage', 25))]); }
    public function show(LivretLesson $lesson): JsonResponse { return response()->json(['data' => $lesson->load(['student.user', 'contract'])]); }
    public function store(Request $request, LessonService $service): JsonResponse { $lesson = $service->create($request->user(), $this->validated($request)); return response()->json(['data' => $lesson], 201); }
    public function update(Request $request, LivretLesson $lesson, LessonService $service): JsonResponse { return response()->json(['data' => $service->update($lesson, $this->validated($request, false))]); }
    public function destroy(LivretLesson $lesson, LessonService $service): JsonResponse { $service->archive($lesson); return response()->json(status: 204); }
    public function retry(LivretLesson $lesson, LessonService $service, LivretSyncService $sync): JsonResponse { $service->retry($lesson, $sync); return response()->json(['data' => $lesson->fresh()]); }

    private function validated(Request $request, bool $creating = true): array
    {
        $rules = [
            'studentId' => [$creating ? 'required' : 'sometimes', 'uuid', Rule::exists('students', 'id')],
            'trainingContractId' => [$creating ? 'required' : 'sometimes', 'uuid', Rule::exists('livret_training_contracts', 'id')],
            'lessonDate' => [$creating ? 'required' : 'sometimes', 'date', 'before_or_equal:today'],
            'startedAt' => [$creating ? 'required' : 'sometimes', 'date'],
            'endedAt' => [$creating ? 'required' : 'sometimes', 'date', 'after:startedAt', 'before_or_equal:now'],
            'durationMinutes' => [$creating ? 'required' : 'sometimes', 'integer', 'between:'.config('livret.lesson_duration_min', 30).','.config('livret.lesson_duration_max', 240)],
            'lessonType' => [$creating ? 'required' : 'sometimes', Rule::in(LivretLesson::TYPES)],
            'permitCategory' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'vehicleType' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'trainerId' => ['nullable', 'string', 'max:100'], 'providerTrainerId' => ['nullable', 'string', 'max:100'], 'trainerAuthorization' => ['nullable', 'string', 'max:100'],
            'simulatorId' => ['nullable', 'string', 'max:100'], 'providerSimulatorId' => ['nullable', 'string', 'max:100'], 'simulatorReference' => ['nullable', 'string', 'max:100'],
            'departmentCode' => ['nullable', 'string', 'max:10'], 'localSessionReference' => ['nullable', 'string', 'max:100'],
        ];
        $data = $request->validate($rules);
        $type = $data['lessonType'] ?? $request->route('lesson')?->lesson_type;
        $typeChanged = array_key_exists('lessonType', $data);
        if ($creating || $typeChanged) {
            if ($type === 'simulator') { $request->validate(['simulatorId' => ['required'], 'providerSimulatorId' => ['required']]); }
            if (in_array($type, ['circulation', 'plateau', 'theoretical'], true)) { $request->validate(['trainerId' => ['required'], 'providerTrainerId' => ['required'], 'trainerAuthorization' => ['required']]); }
            if (in_array($type, ['circulation', 'plateau'], true)) { $request->validate(['departmentCode' => ['required']]); }
        }
        $map = ['studentId' => 'student_id', 'trainingContractId' => 'training_contract_id', 'lessonDate' => 'lesson_date', 'startedAt' => 'started_at', 'endedAt' => 'ended_at', 'durationMinutes' => 'duration_minutes', 'lessonType' => 'lesson_type', 'permitCategory' => 'permit_category', 'vehicleType' => 'vehicle_type', 'trainerId' => 'trainer_id', 'providerTrainerId' => 'provider_trainer_id', 'trainerAuthorization' => 'trainer_authorization', 'simulatorId' => 'simulator_id', 'providerSimulatorId' => 'provider_simulator_id', 'simulatorReference' => 'simulator_reference', 'departmentCode' => 'department_code', 'localSessionReference' => 'local_session_reference'];
        return collect($data)->mapWithKeys(fn ($value, $key) => [$map[$key] => $value])->all();
    }
}
