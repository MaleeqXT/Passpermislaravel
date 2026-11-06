<?php

namespace App\Jobs;

use App\Models\LivretLesson;
use App\Models\LivretTrainingContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedLivretSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function handle(): void
    {
        LivretTrainingContract::query()->where('sync_status', 'pending')->where('sync_attempts', '<', 3)->limit(100)->pluck('id')->each(fn ($id) => SyncLivretTrainingContractJob::dispatch($id));
        LivretLesson::query()->where('sync_status', 'pending')->where('sync_attempts', '<', 3)->limit(100)->pluck('id')->each(fn ($id) => SyncLivretLessonJob::dispatch($id));
    }
}
