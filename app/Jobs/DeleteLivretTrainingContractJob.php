<?php

namespace App\Jobs;

use App\Models\LivretTrainingContract;
use App\Services\LivretNumerique\LivretProviderGateway;
use App\Services\LivretNumerique\LivretSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class DeleteLivretTrainingContractJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public string $contractId) {}
    public function middleware(): array { return [(new WithoutOverlapping('livret-contract-delete:'.$this->contractId))->expireAfter(900)]; }
    public function handle(LivretProviderGateway $provider, LivretSyncService $sync): void
    {
        $contract = LivretTrainingContract::query()->find($this->contractId);
        if (! $contract || ! $contract->provider_contract_id) return;
        $sync->attempt($contract, 'delete_contract');
        try { $provider->deleteTrainingContract($contract->syncUser, $contract); } catch (\Throwable $error) { $sync->failed($contract, 'delete_contract', $error); }
    }
}
