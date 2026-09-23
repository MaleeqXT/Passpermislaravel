<?php
namespace App\Services;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionResponse;
use Illuminate\Support\Facades\Log;
class SatisfactionStageService {
    /** Satisfaction availability is driven by an offer-scoped started response. */
    public function availableFor(Student $student): ?string {
        if (! $student->exists || (int) $student->status === 0) {
            $stage = null;
        } else {
            $stage = SatisfactionResponse::query()->where('candidate_id', $student->id)->where('status', 'started')->latest()->value('survey_id')
                ? SatisfactionResponse::query()->where('candidate_id', $student->id)->where('status', 'started')->latest()->first()?->survey?->stage
                : null;
        }
        Log::info('Satisfaction stage resolved', [
            'student_id' => $student->id ?? null,
            'user_id' => $student->user_id ?? null,
            'student_exists' => $student->exists,
            'student_status' => $student->status ?? null,
            'stage' => $stage,
        ]);
        return $stage;
    }
}
