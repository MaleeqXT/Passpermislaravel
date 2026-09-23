<?php

namespace App\Services;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SatisfactionFirstHourTriggerService
{
    /**
     * Makes the During survey available only after a monitor confirms that the
     * student's first scheduled lesson for the same offer was attended.
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

        // A multi-hour package creates one training/reservation row per booked
        // session. A student's older offer must not prevent the first session
        // of the current offer from unlocking the During survey.
        $firstReservationId = Reservation::query()
            ->whereNull('reservations.deleted_at')
            ->whereHas('training', static function ($query) use ($student, $training) {
                $query->where('student_id', $student->id)
                    ->where('offer_id', $training->offer_id)
                    ->whereNull('trainings.deleted_at');
            })
            ->orderBy('date')
            ->orderBy('start_at')
            ->value('id');

        if ($firstReservationId !== $reservation->id) {
            Log::info('Satisfaction first-hour trigger skipped: not the first session of this offer.', [
                'student_id' => $student->id,
                'offer_id' => $training->offer_id,
                'reservation_id' => $reservation->id,
                'first_reservation_id' => $firstReservationId,
            ]);
            return false;
        }

        $survey = SatisfactionSurvey::query()
            ->where('stage', 'during_training')
            ->where('is_active', true)
            ->first();

        if (! $survey) {
            Log::warning('Satisfaction first-hour trigger skipped: During survey missing.', ['student_id' => $student->id]);
            return false;
        }

        $response = SatisfactionResponse::firstOrCreate(
            ['survey_id' => $survey->id, 'candidate_id' => $student->id],
            ['status' => 'started']
        );

        $notification = SatisfactionNotification::firstOrCreate(
            ['candidate_id' => $student->id, 'type' => 'during_training_first_hour'],
            [
                'response_id' => $response->id,
                'title' => '🚗 Enquête de satisfaction',
                'message' => 'Votre première heure de formation est terminée. Donnez-nous votre avis.',
            ]
        );

        // A response is the durable idempotency marker for this trigger. The
        // notification can be repaired independently without sending mail again.
        if ($response->wasRecentlyCreated && $student->user?->email) {
            try {
                Notification::route('mail', $student->user->email)
                    ->notify(new FirstHourSatisfactionNotification());
            } catch (\Throwable $exception) {
                Log::error('Satisfaction first-hour email could not be sent.', [
                    'student_id' => $student->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('Satisfaction During survey triggered after first completed lesson.', [
            'student_id' => $student->id,
            'reservation_id' => $reservation->id,
            'offer_id' => $training->offer_id,
            'response_id' => $response->id,
            'notification_id' => $notification->id,
            'response_created' => $response->wasRecentlyCreated,
        ]);

        return $response->wasRecentlyCreated || $notification->wasRecentlyCreated;
    }
}
