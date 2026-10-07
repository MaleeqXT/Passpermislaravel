<?php

namespace App\Services;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use Illuminate\Support\Facades\Notification;

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
        foreach (['before_training', 'during_training', 'after_training'] as $stage) {
            if ($this->completed($student, $stage)) {
                continue;
            }

            if (! $this->eligible($student, $stage)) {
                // Before applies only to the evaluation purchase; students who
                // buy other offers can still reach During through their lessons.
                if ($stage === 'before_training') {
                    continue;
                }
                return null;
            }

            return $this->activate($student, $stage);
        }

        return null;
    }

    private function eligible(Student $student, string $stage): bool
    {
        return match ($stage) {
            'before_training' => $this->hasConfirmedEvaluationPurchase($student),
            'during_training' => $this->hasReachedTrainingThreshold($student),
            'after_training' => $student->exams()->whereNotNull('date_examen')->exists(),
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
            ->whereHas('reservation', function ($query) use ($student) {
                $query->where('is_active', true)
                    ->isPassed()
                    ->whereHas('training', fn ($training) => $training->where('student_id', $student->id));
            })
            ->with('reservation.training.offer')
            ->get()
            ->groupBy(fn (ReviewMonitor $review) => $review->reservation?->training?->offer_id)
            ->map(fn ($reviews) => (float) $reviews->sum(fn (ReviewMonitor $review) => (float) ($review->reservation?->hour ?? 0)));

        foreach ($completedHoursByOffer as $offerId => $usedHours) {
            $offer = optional($student->trainings()->where('offer_id', $offerId)->with('offer')->first())->offer;
            $totalHours = (float) ($offer?->balance ?? 0);
            if ($totalHours > 0 && $usedHours >= $this->duringThreshold($totalHours)) {
                return true;
            }
        }

        return false;
    }

    private function duringThreshold(float $hours): float
    {
        // Business examples: 1h -> 1h, 5h -> 3h, 10h -> 7h, 20h -> 14h.
        return $hours <= 1 ? 1 : (float) ceil($hours * 0.70);
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

        SatisfactionNotification::firstOrCreate(
            ['candidate_id' => $student->id, 'survey_id' => $survey->id],
            [
                'response_id' => $response->id,
                'type' => "satisfaction_{$stage}",
                'title' => '🚗 Votre avis compte',
                'message' => 'Donnez-nous votre avis sur votre expérience chez PassPermisFacile.',
            ]
        );

        if ($response->wasRecentlyCreated && $student->user?->email) {
            try {
                Notification::sendNow(
                    Notification::route('mail', $student->user->email),
                    new FirstHourSatisfactionNotification($stage)
                );
            } catch (\Throwable) {
                // Availability remains durable even if the mail transport is down.
            }
        }

        return $response;
    }
}
