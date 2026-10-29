<?php

namespace App\Services;

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Models\SatisfactionEnrollment;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use App\Models\SatisfactionSurvey;
use App\Notifications\V1\Student\Satisfaction\FirstHourSatisfactionNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class SatisfactionLifecycleService
{
    private ?bool $hasDurationMinutesColumn = null;

    public function evaluate(Student $student): ?SatisfactionResponse
    {
        return $this->evaluateAll($student)->first();
    }

    /** @return Collection<int, SatisfactionResponse> */
    public function evaluateAll(Student $student): Collection
    {
        $enrollments = $this->synchronizePaidEnrollments($student);
        $responses = collect();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->is_first_hour && ! $this->completed($student, 'before_training', $enrollment)) {
                $responses->push($this->activate($student, 'before_training', $enrollment));
            }
            if ($enrollment->is_driving_package
                && ! $this->completed($student, 'during_training', $enrollment)
                && $this->completedMinutes($student, $enrollment) >= $this->duringThreshold($enrollment->purchased_minutes)) {
                $responses->push($this->activate($student, 'during_training', $enrollment));
            }
        }

        // The official pending date is the authoritative After event. A
        // reschedule selects this same current paid package and database key.
        $currentEnrollment = $enrollments->where('is_driving_package', true)->sortByDesc('created_at')->first();
        if ($currentEnrollment && $this->hasConfirmedExamDate($student)
            && ! $this->completed($student, 'after_training', $currentEnrollment)) {
            $responses->push($this->activate($student, 'after_training', $currentEnrollment));
        }

        return $responses->filter();
    }

    /** Build immutable paid-purchase snapshots; price is never eligibility. */
    private function synchronizePaidEnrollments(Student $student): Collection
    {
        $firstHourOfferIds = array_values(array_filter((array) config('satisfaction.before_training_first_hour_offer_ids', [])));
        $sales = Sale::query()->withoutGlobalScopes()->withTrashed()
            ->where('student_id', $student->id)->where('payment_status', SaleStatusEnum::PAID->value)
            ->with(['cart' => function ($cart): void {
                $cart->withoutGlobalScopes()->withTrashed()->with(['cartDetails' => function ($details): void {
                    $details->withTrashed()->with(['offer' => fn ($offer) => $offer->withTrashed()]);
                }]);
            }])->get();

        foreach ($sales as $sale) {
            foreach ($sale->cart?->cartDetails ?? [] as $detail) {
                $offer = $detail->offer;
                if (! $offer) continue;
                $isFirstHour = in_array($offer->id, $firstHourOfferIds, true);
                $isDrivingPackage = (int) $offer->type === OffreTypeStatusEnum::CONDUITE->value
                    && (int) $offer->type_offre === OffreTypeEnum::FORFAIT->value;
                if (! $isFirstHour && ! $isDrivingPackage) continue;

                // Sale.balance is the immutable quantity credited by the
                // confirmed payment (and already includes selected menu
                // quantities/installments).  Never use the mutable wallet or
                // current offer balance for a satisfaction threshold.
                $hours = (float) ($sale->balance ?? 0);
                $minutes = max(0, (int) round($hours * 60));
                // A first-hour survey has no hour-threshold.  A driving
                // package without a payment snapshot must not be guessed from
                // the offer or wallet balance.
                if ($isDrivingPackage && $minutes === 0) continue;
                SatisfactionEnrollment::firstOrCreate(
                    ['sale_id' => $sale->id, 'offer_id' => $offer->id],
                    ['student_id' => $student->id, 'purchased_minutes' => $minutes, 'is_first_hour' => $isFirstHour, 'is_driving_package' => $isDrivingPackage],
                );
            }
        }

        return SatisfactionEnrollment::query()->where('student_id', $student->id)->orderBy('created_at')->get();
    }

    private function completed(Student $student, string $stage, SatisfactionEnrollment $enrollment): bool
    {
        return SatisfactionResponse::query()->where('candidate_id', $student->id)
            ->where('satisfaction_enrollment_id', $enrollment->id)->where('status', 'completed')
            ->whereHas('survey', fn (Builder $query) => $query->where('stage', $stage))->exists();
    }

    private function hasConfirmedExamDate(Student $student): bool
    {
        return $student->exams()->whereNotNull('date_examen')
            ->where('status', ExamenStatusEnum::PENDING->value)->whereNull('result_permis')->exists();
    }

    private function completedMinutes(Student $student, SatisfactionEnrollment $enrollment): int
    {
        $this->assignUnattributedCompletedLessons($student, $enrollment->offer_id);
        return (int) ReviewMonitor::query()->where('review_monitors.is_absent', false)
            ->whereNotNull('review_monitors.comment')->whereRaw("TRIM(review_monitors.comment) <> ''")
            ->whereHas('reservation', function (Builder $reservation) use ($student, $enrollment): void {
                $reservation->where('is_active', true)->isPassed()->whereHas('training', function (Builder $training) use ($student, $enrollment): void {
                    $training->where('student_id', $student->id)->where('satisfaction_enrollment_id', $enrollment->id)
                        ->whereDoesntHave('cancellation', fn (Builder $cancel) => $cancel->where('status', CancellationStatusEnum::SUCCESS_CANCELLED->value));
                });
            })->with($this->reservationDurationColumns())->get()
            ->sum(fn (ReviewMonitor $review) => $this->durationMinutes($review));
    }

    /** Persist FIFO attribution for legacy rows that have only an offer ID. */
    private function assignUnattributedCompletedLessons(Student $student, string $offerId): void
    {
        DB::transaction(function () use ($student, $offerId): void {
            $enrollments = SatisfactionEnrollment::query()->where('student_id', $student->id)->where('offer_id', $offerId)
                ->where('is_driving_package', true)->orderBy('created_at')->lockForUpdate()->get();
            if ($enrollments->isEmpty()) return;

            $allocated = Training::query()->whereIn('satisfaction_enrollment_id', $enrollments->pluck('id'))
                ->with('reservation.reviewMonitor')->get()->groupBy('satisfaction_enrollment_id')
                ->map(fn ($trainings) => $trainings->sum(function (Training $training): int {
                    $review = $training->reservation?->reviewMonitor;
                    return $review && ! $review->is_absent && filled(trim((string) $review->comment)) ? $this->durationMinutes($review) : 0;
                }));
            $reviews = ReviewMonitor::query()->where('review_monitors.is_absent', false)->whereNotNull('review_monitors.comment')
                ->whereRaw("TRIM(review_monitors.comment) <> ''")->with(['reservation.training'])
                ->whereHas('reservation', function (Builder $reservation) use ($student, $offerId): void {
                    $reservation->where('is_active', true)->isPassed()->whereHas('training', function (Builder $training) use ($student, $offerId): void {
                        $training->where('student_id', $student->id)->where('offer_id', $offerId)->whereNull('satisfaction_enrollment_id')
                            ->whereDoesntHave('cancellation', fn (Builder $cancel) => $cancel->where('status', CancellationStatusEnum::SUCCESS_CANCELLED->value));
                    });
                })->orderBy('review_monitors.id')->lockForUpdate()->get();
            foreach ($reviews as $review) {
                $training = $review->reservation?->training;
                if (! $training || $training->satisfaction_enrollment_id) continue;
                $minutes = $this->durationMinutes($review);
                $enrollment = $enrollments->first(fn (SatisfactionEnrollment $candidate) => ((int) ($allocated[$candidate->id] ?? 0) + $minutes) <= $candidate->purchased_minutes) ?? $enrollments->last();
                $training->update(['satisfaction_enrollment_id' => $enrollment->id]);
                $allocated[$enrollment->id] = (int) ($allocated[$enrollment->id] ?? 0) + $minutes;
            }
        });
    }

    private function durationMinutes(ReviewMonitor $review): int
    {
        $reservation = $review->reservation;
        if ($this->hasDurationMinutesColumn() && $reservation?->duration_minutes !== null) {
            return max(0, (int) $reservation->duration_minutes);
        }

        return max(0, (int) round((float) ($reservation?->hour ?? 0) * 60));
    }

    private function reservationDurationColumns(): string
    {
        return $this->hasDurationMinutesColumn()
            ? 'reservation:id,duration_minutes,hour'
            : 'reservation:id,hour';
    }

    private function hasDurationMinutesColumn(): bool
    {
        return $this->hasDurationMinutesColumn ??= Schema::hasColumn('reservations', 'duration_minutes');
    }

    private function duringThreshold(int $totalMinutes): int
    {
        $percentage = min(100, max(0, (float) config('satisfaction.during_training_threshold_percent', 75)));
        return (int) ceil($totalMinutes * ($percentage / 100));
    }

    private function activate(Student $student, string $stage, SatisfactionEnrollment $enrollment): ?SatisfactionResponse
    {
        $survey = SatisfactionSurvey::query()->where('stage', $stage)->where('is_active', true)->first();
        if (! $survey) return null;
        $response = SatisfactionResponse::firstOrCreate(
            ['survey_id' => $survey->id, 'candidate_id' => $student->id, 'satisfaction_enrollment_id' => $enrollment->id],
            ['offer_id' => $enrollment->offer_id, 'status' => 'started'],
        );
        $notification = SatisfactionNotification::firstOrCreate(
            ['candidate_id' => $student->id, 'survey_id' => $survey->id, 'satisfaction_enrollment_id' => $enrollment->id],
            ['offer_id' => $enrollment->offer_id, 'response_id' => $response->id, 'type' => "satisfaction_{$stage}", 'title' => '🚗 Votre avis compte', 'message' => 'Donnez-nous votre avis sur votre expérience chez PassPermisFacile.'],
        );
        $this->deliverEmail($notification, $student, $stage, $response->id);
        return $response;
    }

    private function deliverEmail(SatisfactionNotification $notification, Student $student, string $stage, string $responseId): void
    {
        if (! $student->user?->email || $notification->email_sent_at || $notification->last_email_attempt_at?->greaterThan(now()->subMinutes(15))) return;
        $notification->increment('email_attempts');
        $notification->update(['last_email_attempt_at' => now()]);
        try {
            $this->sendSurveyEmail($student, $stage, $responseId);
            $notification->update(['email_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Satisfaction survey email could not be delivered.', ['student_id' => $student->id, 'notification_id' => $notification->id, 'stage' => $stage, 'message' => $exception->getMessage()]);
        }
    }

    protected function sendSurveyEmail(Student $student, string $stage, string $responseId): void
    {
        Notification::sendNow(Notification::route('mail', $student->user->email), new FirstHourSatisfactionNotification($stage, $responseId));
    }
}
