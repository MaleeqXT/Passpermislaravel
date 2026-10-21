<?php

namespace App\Services;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SatisfactionLifecycleService
{
    private const BEFORE_EVALUATION_OFFER_IDS = [
        '84aeacd3-a396-11f1-a172-9840bb4923f7', // Pass permis Heure d’évaluation BM
        '9eec8bb6-af81-4846-b729-5c646ead54f4', // Pass permis Heure d’évaluation BA
    ];

    /**
     * Reconcile the next applicable survey. This may be called safely after a
     * payment, a monitor review, an exam update, or when the student opens the
     * survey page. Database uniqueness keeps every stage student-level.
     */
    public function evaluate(Student $student): ?SatisfactionResponse
    {
        return $this->evaluateAll($student)->first();
    }

    /**
     * Reconcile every applicable stage. A student may legitimately have more
     * than one pending questionnaire (for example, an exam date is assigned
     * after they have reached the training threshold). Each stage remains
     * independently unique at database level.
     *
     * @return Collection<int, SatisfactionResponse>
     */
    public function evaluateAll(Student $student): Collection
    {
        $responses = collect();

        foreach (['before_training', 'during_training', 'after_training'] as $stage) {
            if ($this->completed($student, $stage)) {
                continue;
            }

            if (! $this->eligible($student, $stage)) {
                continue;
            }

            if ($response = $this->activate($student, $stage)) {
                $responses->push($response);
            }
        }

        return $responses;
    }

    private function eligible(Student $student, string $stage): bool
    {
        return match ($stage) {
            'before_training' => $this->hasConfirmedEvaluationPurchase($student),
            'during_training' => $this->hasReachedTrainingThreshold($student),
            // An assigned date is only an actionable appointment while it is
            // still pending and has not received a final result. Staff clears
            // the date when an appointment is cancelled, so that record can
            // never unlock the after-training questionnaire.
            'after_training' => $student->exams()
                ->whereNotNull('date_examen')
                ->where('status', ExamenStatusEnum::PENDING->value)
                ->whereNull('result_permis')
                ->exists(),
            default => false,
        };
    }

    private function completed(Student $student, string $stage): bool
    {
        return SatisfactionResponse::query()
            ->where('candidate_id', $student->id)
            ->where('status', 'completed')
            ->whereHas('survey', fn ($query) => $query->where('stage', $stage))
            ->exists();
    }

    private function hasConfirmedEvaluationPurchase(Student $student): bool
    {
        return Sale::query()
            ->where('student_id', $student->id)
            ->where('payment_status', SaleStatusEnum::PAID->value)
            ->where('payment_method', SalePaymentMethodEnum::STRIP->value)
            ->where('payment_id', 'like', 'pi_%')
            ->whereHas('cart.cartDetails', fn ($query) => $query->whereIn('offer_id', self::BEFORE_EVALUATION_OFFER_IDS))
            ->exists();
    }

    private function hasReachedTrainingThreshold(Student $student): bool
    {
        $completedHoursByOffer = ReviewMonitor::query()
            ->where('review_monitors.is_absent', false)
            ->whereNotNull('review_monitors.comment')
            ->whereRaw("TRIM(review_monitors.comment) <> ''")
            ->whereHas('reservation', function ($query) use ($student) {
                $query->where('is_active', true)
                    ->isPassed()
                    ->whereHas('training', fn ($training) => $training
                        ->where('student_id', $student->id)
                        ->whereDoesntHave('cancellation', fn ($cancellation) => $cancellation
                            ->where('status', CancellationStatusEnum::SUCCESS_CANCELLED->value)));
            })
            ->with('reservation.training.offer')
            ->get()
            ->groupBy(fn (ReviewMonitor $review) => $review->reservation?->training?->offer_id)
            ->map(fn ($reviews) => (int) $reviews->sum(fn (ReviewMonitor $review) => $this->durationMinutes($review)));

        foreach ($completedHoursByOffer as $offerId => $usedMinutes) {
            $offer = optional($student->trainings()->where('offer_id', $offerId)->with('offer')->first())->offer;
            $totalMinutes = max(0, (int) ($offer?->balance ?? 0) * 60);
            if ($totalMinutes > 0 && $usedMinutes >= $this->duringThreshold($totalMinutes)) {
                return true;
            }
        }

        return false;
    }

    private function durationMinutes(ReviewMonitor $review): int
    {
        $reservation = $review->reservation;

        return $reservation?->duration_minutes === null
            ? max(0, (int) round((float) ($reservation?->hour ?? 0) * 60))
            : max(0, (int) $reservation->duration_minutes);
    }

    private function duringThreshold(int $totalMinutes): int
    {
        // The default is 75%: 20h => 15h, 10h => 7h30. Durations are kept in
        // minutes so a 45-minute lesson never gets rounded away.
        return (int) ceil($totalMinutes * 0.75);
    }

    private function activate(Student $student, string $stage): ?SatisfactionResponse
    {
        $survey = SatisfactionSurvey::query()->where('stage', $stage)->where('is_active', true)->first();
        if (! $survey) {
            return null;
        }

        $response = SatisfactionResponse::firstOrCreate(
            ['survey_id' => $survey->id, 'candidate_id' => $student->id],
            ['status' => 'started']
        );

        $notification = SatisfactionNotification::firstOrCreate(
            ['candidate_id' => $student->id, 'survey_id' => $survey->id],
            [
                'response_id' => $response->id,
                'type' => "satisfaction_{$stage}",
                'title' => '🚗 Votre avis compte',
                'message' => 'Donnez-nous votre avis sur votre expérience chez PassPermisFacile.',
            ]
        );

        $this->deliverEmail($notification, $student, $stage);

        return $response;
    }

    private function deliverEmail(SatisfactionNotification $notification, Student $student, string $stage): void
    {
        if (! $student->user?->email || $notification->email_sent_at) {
            return;
        }

        // A failed transport remains retryable, but ordinary page refreshes do
        // not repeatedly send the same email.
        if ($notification->last_email_attempt_at?->greaterThan(now()->subMinutes(15))) {
            return;
        }

        $notification->increment('email_attempts');
        $notification->update(['last_email_attempt_at' => now()]);

        try {
            $this->sendSurveyEmail($student, $stage);
            $notification->update(['email_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Satisfaction survey email could not be delivered.', [
                'student_id' => $student->id,
                'notification_id' => $notification->id,
                'stage' => $stage,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Kept behind a protected seam so the delivery bookkeeping can be tested
     * without ever opening a real SMTP connection.
     */
    protected function sendSurveyEmail(Student $student, string $stage): void
    {
        Notification::sendNow(
            Notification::route('mail', $student->user->email),
            new FirstHourSatisfactionNotification($stage)
        );
    }
}
