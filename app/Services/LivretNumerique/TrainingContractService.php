<?php

namespace App\Services\LivretNumerique;

use App\Jobs\SyncLivretTrainingContractJob;
use App\Jobs\DeleteLivretTrainingContractJob;
use App\Models\LivretTrainingContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingContractService
{
    public function create(User $actor, array $data): LivretTrainingContract
    {
        return DB::transaction(function () use ($actor, $data) {
            $contract = LivretTrainingContract::create([...$data, 'sync_user_id' => $actor->getKey(), 'sync_status' => 'pending']);
            SyncLivretTrainingContractJob::dispatch($contract->getKey());
            return $contract;
        });
    }

    public function update(LivretTrainingContract $contract, array $data): LivretTrainingContract
    {
        if ($contract->sync_status === 'sending') { abort(409, 'Une synchronisation Livret est déjà en cours.'); }
        $contract->fill($data);
        if ($contract->isDirty()) {
            $contract->forceFill(['sync_status' => $contract->provider_contract_id ? 'needs_resync' : 'pending', 'provider_error_code' => null, 'provider_error_message' => null])->save();
        }
        return $contract->fresh();
    }

    public function archive(LivretTrainingContract $contract): void
    {
        if ($contract->lessons()->whereNull('archived_at')->exists()) { abort(409, 'Archivez d’abord les leçons liées à ce contrat.'); }
        $contract->forceFill(['sync_status' => 'deleted', 'archived_at' => now()])->save();
        if ($contract->provider_contract_id) { DeleteLivretTrainingContractJob::dispatch($contract->getKey()); }
    }

    public function retry(LivretTrainingContract $contract, LivretSyncService $sync): void
    {
        abort_unless($sync->canRetry($contract), 422, 'Ce contrat Livret ne peut pas être relancé.');
        $contract->update(['sync_status' => 'pending', 'provider_error_code' => null, 'provider_error_message' => null]);
        SyncLivretTrainingContractJob::dispatch($contract->getKey());
    }
}
