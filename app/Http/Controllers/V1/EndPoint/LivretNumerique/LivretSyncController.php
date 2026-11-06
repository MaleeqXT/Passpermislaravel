<?php

namespace App\Http\Controllers\V1\EndPoint\LivretNumerique;

use App\Http\Controllers\Controller;
use App\Models\LivretLesson;
use App\Models\LivretSyncLog;
use App\Models\LivretTrainingContract;
use App\Services\LivretNumerique\SimulatorService;
use App\Services\LivretNumerique\TrainerService;
use Illuminate\Http\JsonResponse;

class LivretSyncController extends Controller
{
    public function status(): JsonResponse { return response()->json(['data' => ['contracts' => $this->counts(LivretTrainingContract::class), 'lessons' => $this->counts(LivretLesson::class), 'recentFailures' => LivretSyncLog::query()->whereIn('result', ['failed', 'needs_resync'])->latest('created_at')->limit(20)->get(), 'providerSchemaVerified' => false]]); }
    public function failures(): JsonResponse { return response()->json(['data' => LivretSyncLog::query()->whereIn('result', ['failed', 'needs_resync'])->latest('created_at')->paginate(50)]); }
    public function trainers(TrainerService $trainers): JsonResponse { return response()->json($trainers->mappings()); }
    public function simulators(SimulatorService $simulators): JsonResponse { return response()->json($simulators->mappings()); }
    private function counts(string $model): array { return collect(['pending', 'sending', 'synced', 'failed', 'needs_resync'])->mapWithKeys(fn ($status) => [$status => $model::query()->where('sync_status', $status)->count()])->all(); }
}
