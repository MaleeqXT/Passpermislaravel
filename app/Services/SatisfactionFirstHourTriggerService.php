<?php

namespace App\Services;

use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use Illuminate\Support\Facades\Log;

class SatisfactionFirstHourTriggerService
{
    /**
     * Legacy entry point retained for callers from older releases. The
     * lifecycle service is the sole source of truth: it applies the current
     * 75%-of-completed-minutes rule and delivery tracking.
     */
    public function process(ReviewMonitor $review): bool
    {
        $review->loadMissing('reservation.training.student.user');
        $reservation = $review->reservation;
        $training = $reservation?->training;
        $student = $training?->student;

        if (! $reservation || ! $training || ! $student || $review->is_absent || blank($review->comment)
            || $reservation->trashed() || $training->trashed()) {
            Log::info('Satisfaction first-hour trigger skipped: lesson is not a confirmed present training.', [
                'review_id' => $review->id,
                'reservation_id' => $reservation?->id,
                'is_absent' => $review->is_absent,
                'has_comment' => filled($review->comment),
            ]);
            return false;
        }

        $responses = app(SatisfactionLifecycleService::class)->evaluateAll($student);

        Log::info('Legacy satisfaction first-hour entry reconciled through lifecycle.', [
            'student_id' => $student->id,
            'reservation_id' => $reservation->id,
            'offer_id' => $training->offer_id,
            'response_count' => $responses->count(),
        ]);

        return $responses->isNotEmpty();
    }
}
