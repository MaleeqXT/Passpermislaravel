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

class DeleteLivretLessonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public string $lessonId) {}
    public function middleware(): array { return [(new WithoutOverlapping('livret-lesson-delete:'.$this->lessonId))->expireAfter(900)]; }
    public function handle(LivretProviderGateway $provider, LivretSyncService $sync): void
    {
        $lesson = LivretLesson::query()->find($this->lessonId);
        if (! $lesson || ! $lesson->provider_lesson_id) return;
        $sync->attempt($lesson, 'delete_lesson');
        try { $provider->deleteLesson($lesson->syncUser, $lesson); } catch (\Throwable $error) { $sync->failed($lesson, 'delete_lesson', $error); }
    }
}
