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

class SyncLivretTrainingContractJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $backoff = [60, 300, 900];
    public function __construct(public string $contractId) {}
    public function middleware(): array { return [(new WithoutOverlapping('livret-contract:'.$this->contractId))->expireAfter(900)]; }
    public function handle(LivretProviderGateway $provider, LivretSyncService $sync): void
    {
        $contract = LivretTrainingContract::query()->find($this->contractId);
        if (! $contract || in_array($contract->sync_status, ['synced', 'deleted'], true)) return;
        $sync->attempt($contract, 'create_contract');
        try {
            $provider->createTrainingContract($contract->syncUser, $contract);
        } catch (\Throwable $error) {
            $sync->failed($contract, 'create_contract', $error);
            return;
        }
    }
}
