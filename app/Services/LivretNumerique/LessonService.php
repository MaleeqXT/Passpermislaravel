<?php

namespace App\Services\LivretNumerique;

use App\Jobs\SyncLivretLessonJob;
use App\Jobs\DeleteLivretLessonJob;
use App\Models\LivretLesson;
use App\Models\LivretTrainingContract;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LessonService
{
    public function create(User $actor, array $data): LivretLesson
    {
        $contract = LivretTrainingContract::query()->findOrFail($data['training_contract_id']);
        abort_unless($contract->student_id === $data['student_id'], 422, 'Le contrat Livret ne correspond pas à cet élève.');
        abort_unless($contract->sync_status === 'synced', 422, 'Le contrat Livret doit être synchronisé avant une leçon.');
        $this->assertCompleted($data);

        return DB::transaction(function () use ($actor, $data) {
            $lesson = LivretLesson::create([...$data, 'sync_user_id' => $actor->getKey(), 'sync_status' => 'pending']);
            SyncLivretLessonJob::dispatch($lesson->getKey());
            return $lesson;
        });
    }

    public function update(LivretLesson $lesson, array $data): LivretLesson
    {
        if ($lesson->sync_status === 'sending') { abort(409, 'Une synchronisation Livret est déjà en cours.'); }
        $merged = array_replace($lesson->toArray(), $data);
        $this->assertCompleted($merged);
        $lesson->fill($data);
        if ($lesson->isDirty()) { $lesson->forceFill(['sync_status' => $lesson->provider_lesson_id ? 'needs_resync' : 'pending', 'provider_error_code' => null, 'provider_error_message' => null])->save(); }
        return $lesson->fresh();
    }

    public function archive(LivretLesson $lesson): void
    {
        $lesson->forceFill(['sync_status' => 'deleted', 'archived_at' => now()])->save();
        if ($lesson->provider_lesson_id) { DeleteLivretLessonJob::dispatch($lesson->getKey()); }
    }

    public function retry(LivretLesson $lesson, LivretSyncService $sync): void
    {
        abort_unless($sync->canRetry($lesson), 422, 'Cette leçon Livret ne peut pas être relancée.');
        $this->assertCompleted($lesson->toArray());
        $lesson->update(['sync_status' => 'pending', 'provider_error_code' => null, 'provider_error_message' => null]);
        SyncLivretLessonJob::dispatch($lesson->getKey());
    }

    private function assertCompleted(array $data): void
    {
        $endedAt = $data['ended_at'] ?? null;
        abort_unless($endedAt && Carbon::parse($endedAt)->isPast(), 422, 'Une leçon ne peut être synchronisée qu’après sa fin.');
    }
}
