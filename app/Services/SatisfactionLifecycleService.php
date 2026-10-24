<?php

namespace App\Services;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
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
use Illuminate\Database\Eloquent\Builder;

class SatisfactionLifecycleService
{

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
            'before_training' => $this->hasConfirmedDrivingPackagePurchase($student),
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

    /**
     * A Before survey belongs to the student's driving enrolment, not to a
     * particular Stripe payment intent.  Paid cash, bank-transfer and PayPal
     * packages are just as confirmed as a paid Stripe package.  A package is
     * identified from the offer itself so an unrelated code product can never
     * unlock the driving-training questionnaire.
     */
    private function hasConfirmedDrivingPackagePurchase(Student $student): bool
    {
        return Sale::query()
            ->where('student_id', $student->id)
            ->where('payment_status', SaleStatusEnum::PAID->value)
            // A paid invoice remains proof of purchase even when staff later
            // archives its cart, cart line, or offer.  The normal cart scope
            // hides CPF carts, but it must not hide a confirmed purchase from
            // this lifecycle check.
            ->whereHas('cart', function (Builder $cart) {
                $cart->withoutGlobalScopes()
                    ->withTrashed()
                    ->whereHas('cartDetails', function (Builder $cartDetails) {
                        $cartDetails->withTrashed()
                            ->whereHas('offer', function (Builder $query) {
                                $legacyFirstHourOfferIds = array_values(array_filter(
                                    (array) config('satisfaction.before_training_first_hour_offer_ids', [])
                                ));

                                // Include first-hour/evaluation offers (€38/€45) through the
                                // explicit business flag, even if legacy records predate the
                                // normal driving-package categorisation.
                                $query->withTrashed()->where(function ($offer) use ($legacyFirstHourOfferIds) {
                                    $offer->where('is_evaluation', true)
                                        ->orWhere(function ($drivingPackage) {
                                            $drivingPackage
                                                ->where('type', OffreTypeStatusEnum::CONDUITE->value)
                                                ->where('type_offre', OffreTypeEnum::FORFAIT->value);
                                        })
                                        ->when($legacyFirstHourOfferIds, fn ($legacy) => $legacy->orWhereIn('id', $legacyFirstHourOfferIds));
                                });
                            });
                    });
            })
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
        // minutes so a 45-minute lesson never gets rounded away. An operator
        // can adjust SATISFACTION_DURING_THRESHOLD_PERCENT without changing
        // business code.
        $percentage = (float) config('satisfaction.during_training_threshold_percent', 75);
        $percentage = min(100, max(0, $percentage));

        return (int) ceil($totalMinutes * ($percentage / 100));
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
