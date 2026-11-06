<?php

namespace App\Http\Controllers\V1\EndPoint\LivretNumerique;

use App\Http\Controllers\Controller;
use App\Models\LivretTrainingContract;
use App\Services\LivretNumerique\LivretSyncService;
use App\Services\LivretNumerique\TrainingContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingContractController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => LivretTrainingContract::query()->with('student.user')->whereNull('archived_at')->latest()->paginate((int) $request->integer('perPage', 25))]); }
    public function show(LivretTrainingContract $contract): JsonResponse { return response()->json(['data' => $contract->load(['student.user', 'lessons'])]); }
    public function store(Request $request, TrainingContractService $service): JsonResponse { $contract = $service->create($request->user(), $this->validated($request)); return response()->json(['data' => $contract], 201); }
    public function update(Request $request, LivretTrainingContract $contract, TrainingContractService $service): JsonResponse { return response()->json(['data' => $service->update($contract, $this->validated($request))]); }
    public function destroy(LivretTrainingContract $contract, TrainingContractService $service): JsonResponse { $service->archive($contract); return response()->json(status: 204); }
    public function retry(LivretTrainingContract $contract, TrainingContractService $service, LivretSyncService $sync): JsonResponse { $service->retry($contract, $sync); return response()->json(['data' => $contract->fresh()]); }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'studentId' => ['required', 'uuid', Rule::exists('students', 'id')],
            'requestNumber' => ['required', 'string', 'max:100'],
            'registrationDate' => ['required', 'date', 'before_or_equal:today'],
            'trainingType' => ['required', 'string', 'max:100'],
            'permitCategory' => ['required', 'string', 'max:100'],
            'vehicleType' => ['required', 'string', 'max:100'],
            'evaluatedHours' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        return [
            'student_id' => $data['studentId'], 'request_number' => $data['requestNumber'], 'registration_date' => $data['registrationDate'],
            'training_type' => $data['trainingType'], 'permit_category' => $data['permitCategory'], 'vehicle_type' => $data['vehicleType'], 'evaluated_hours' => (int) $data['evaluatedHours'],
        ];
    }
}
