<?php

namespace App\Jobs;

use App\Models\LivretLesson;
use App\Services\LivretNumerique\LivretProviderGateway;
use App\Services\LivretNumerique\LivretSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncLivretLessonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $backoff = [60, 300, 900];
    public function __construct(public string $lessonId) {}
    public function middleware(): array { return [(new WithoutOverlapping('livret-lesson:'.$this->lessonId))->expireAfter(900)]; }
    public function handle(LivretProviderGateway $provider, LivretSyncService $sync): void
    {
        $lesson = LivretLesson::query()->with('contract')->find($this->lessonId);
        if (! $lesson || in_array($lesson->sync_status, ['synced', 'deleted'], true)) return;
        if ($lesson->contract?->sync_status !== 'synced') { $lesson->update(['sync_status' => 'needs_resync', 'provider_error_code' => 'CONTRACT_NOT_SYNCED', 'provider_error_message' => 'Le contrat Livret doit être synchronisé avant la leçon.']); return; }
        $sync->attempt($lesson, 'create_lesson');
        try {
            $provider->createLesson($lesson->syncUser, $lesson);
        } catch (\Throwable $error) {
            $sync->failed($lesson, 'create_lesson', $error);
            return;
        }
    }
}
