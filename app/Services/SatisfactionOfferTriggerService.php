<?php

namespace App\Services;

use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SatisfactionOfferTriggerService
{
    /**
     * Evaluate an offer only after a monitor has recorded a completed,
     * attended lesson. Booking a reservation only reserves wallet time; it is
     * not evidence that training time has actually been used.
     */
    public function processCompletedLesson(ReviewMonitor $review): bool
    {
        $review->loadMissing('reservation.training.student.user');

        $reservation = $review->reservation;
        $training = $reservation?->training;
        $student = $training?->student;

        if (! $reservation || ! $training || ! $student
            || $review->is_absent
            || blank($review->comment)
            || ! $reservation->is_active
            || $reservation->trashed()
            || $training->trashed()
            || ! $reservation->date
            || ! $reservation->end_at
            || $reservation->date->copy()->setTimeFrom($reservation->end_at)->isFuture()) {
            return false;
        }

        return $this->evaluateOffer($student, $training->offer_id);
    }

    public function evaluateOffer(Student $student, string $offerId): bool
    {
        $offer = Offer::query()->find($offerId);
        $totalHours = (float) ($offer?->balance ?? 0);
        if (! $offer || $totalHours <= 0) {
            return false;
        }

        // A completed lesson is represented by a non-absent monitor review
        // with a written review. This reuses the existing review workflow and
        // deliberately excludes future, inactive, cancelled and soft-deleted
        // reservations/trainings.
        $usedHours = (float) ReviewMonitor::query()
            ->where('review_monitors.is_absent', false)
            ->whereNotNull('review_monitors.comment')
            ->whereHas('reservation', function ($query) use ($student, $offer) {
                $query->where('is_active', true)
                    ->isPassed()
                    ->whereHas('training', function ($trainingQuery) use ($student, $offer) {
                        $trainingQuery->where('student_id', $student->id)
                            ->where('offer_id', $offer->id);
                    });
            })
            ->with('reservation:id,hour')
            ->get()
            ->sum(fn (ReviewMonitor $completedReview) => (float) ($completedReview->reservation?->hour ?? 0));

        if ($totalHours === 1.0) {
            return $usedHours >= 1 && $this->trigger($student, $offer, 'during_training');
        }

        $hasUsedMoreThanHalf = $usedHours > ($totalHours / 2);

        Log::info('Satisfaction offer-hour evaluation.', [
            'student_id' => $student->id,
            'offer_id' => $offer->id,
            'total_hours' => $totalHours,
            'used_hours' => $usedHours,
            'has_used_more_than_half' => $hasUsedMoreThanHalf,
        ]);

        return $hasUsedMoreThanHalf && $this->trigger($student, $offer, 'during_training');
    }

    private function trigger(Student $student, Offer $offer, string $stage): bool
    {
        $survey = SatisfactionSurvey::query()->where('stage', $stage)->where('is_active', true)->first();
        if (! $survey) {
            Log::warning('Satisfaction offer trigger skipped: active survey missing.', compact('stage') + ['student_id' => $student->id, 'offer_id' => $offer->id]);
            return false;
        }

        $response = SatisfactionResponse::firstOrCreate(
            ['survey_id' => $survey->id, 'candidate_id' => $student->id, 'offer_id' => $offer->id],
            ['status' => 'started']
        );

        $notification = SatisfactionNotification::firstOrCreate(
            ['candidate_id' => $student->id, 'offer_id' => $offer->id, 'survey_id' => $survey->id],
            [
                'response_id' => $response->id,
                'type' => "{$stage}_offer",
                'title' => '🚗 Enquête de satisfaction',
                'message' => 'Votre avis nous aide à améliorer votre expérience.',
            ]
        );

        if ($response->wasRecentlyCreated && $student->user?->email) {
            // Send immediately after the confirmed hour consumption. This is
            // intentionally not left waiting in the database queue, otherwise
            // a missing local queue worker makes the student see no email.
            try {
                Notification::sendNow(
                    Notification::route('mail', $student->user->email),
                    new FirstHourSatisfactionNotification($stage)
                );
            } catch (\Throwable $exception) {
                Log::error('Satisfaction trigger email could not be sent.', [
                    'student_id' => $student->id,
                    'offer_id' => $offer->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('Satisfaction offer survey triggered.', [
            'student_id' => $student->id,
            'offer_id' => $offer->id,
            'stage' => $stage,
            'response_id' => $response->id,
            'notification_id' => $notification->id,
        ]);

        return $response->wasRecentlyCreated || $notification->wasRecentlyCreated;
    }
}
