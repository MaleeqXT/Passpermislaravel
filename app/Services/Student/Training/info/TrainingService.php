<?php

namespace App\Services\Student\Training\info;


use App\Enums\V2\Admin\Popular\CodeErrorsEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Notifications\V1\Monitor\Training\Validation\ValidationTrainingMonitorNotification;
use App\Notifications\V1\Student\Contract\ContractAvailableStudentNotification;
use App\Notifications\V1\Student\Training\Validation\UpdatedTrainingStudentNotification;
use App\Notifications\V1\Student\Training\Validation\ValidationTrainingStudentNotification;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\EditOrCreateReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\EditReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\MonitorInReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\UpdateReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\RefusedAllTrainingProposalRepo;
use App\Repository\V2\Student\Schedule\Training\Job\EditTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Job\StoreTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Job\StudentInTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\FetchWalletRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainingService implements TrainingInterface
{

    /**
     * @param Training $training
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public function update(Training $training, array $attributes): bool
    {
        $plan = $training->reservation;
        $originalPlan = $plan->toArray();
        $hasChanged = $this->hasChanges($training, $attributes);
        $monitorHasChanged = $plan->monitor_id !== $attributes['monitor_id'];

        $attributes['id_not'] = $hasChanged ? $plan->id : null;

        $this->validateStudentAndWallet($training, $attributes);
        $this->validateMonitorAvailability($monitorHasChanged, $attributes); // Keep this validation
        // $this->validateUniquePlanning($attributes, $training->id); // Disabled to allow updates

        $reservation = $this->updateOrCreateReservation($attributes, $monitorHasChanged, $originalPlan);
      // if ($monitorHasChanged) {
            $attributes['reservation_id'] = $reservation->id;
     //   }

        RefusedAllTrainingProposalRepo::run($reservation);

        $updatedTraining = EditTrainingRepo::run($training, Arr::only($attributes, ['student_id', 'reservation_id', 'offer_id', 'session_type', 'prestation']));
        $this->sendTrainingUpdateNotifications($attributes['student_id'], $reservation);

      //  UpdateReservationRepo::run($reservation, Arr::only($attributes, ['monitor_id', 'date', 'start_at', 'end_at', 'hour', 'color', 'lieu_id']));
        return $updatedTraining;
    }

    /**
     * @param array $attributes
     * @param Reservation $reservation
     * @return Training|null|Model
     * @throws Exception
     */
    public function create(array $attributes, Reservation $reservation): Training|null|Model
    {
        $attributes['student_id'] = $attributes['student_id'] ?? auth()->user()->student->id;

        $this->validateStudentWallet($attributes['student_id'], $attributes['offer_id'], $reservation->hour);

        IncOrDecWalletRepo::run($attributes['student_id'], $attributes['offer_id'], WalletStatusEnum::DECREMENT->value, $reservation->hour);

        $attributes['reservation_id'] = $reservation->id;

        $training = StoreTrainingRepo::run(Arr::only($attributes, ['student_id', 'reservation_id', 'offer_id', 'session_type', 'prestation']));

        RefusedAllTrainingProposalRepo::run($reservation);
        $this->sendTrainingNotifications($attributes['student_id'], $reservation);
        $this->sendContractUnlockedNotification($attributes['student_id'], $reservation);

        return $training;
    }

    /**
     * Validate the student's wallet and ensure sufficient balance.
     * @throws Exception
     */
    private function validateStudentWallet(string $studentId, string $offerId, float $requiredHours): void
    {
        $wallet = FetchWalletRepo::run($studentId, ['offer_id' => $offerId]);
            Log::info([
            'student_id' => $studentId,
            'offer_id' => $offerId,
            'wallet_balance' => $wallet?->balance,
            'required_hours' => $requiredHours,
        ]);



        if ($wallet?->balance < $requiredHours) {

            throw new Exception(CodeErrorsEnum::INSUFFICIENT_SOLDE->value);
        }
    }

    /**
     * Check if there are changes in the attributes compared to the current training.
     */
    private function hasChanges(Training $training, array $attributes): bool
    {
        $plan = $training->reservation;
        return $training->student_id != $attributes['student_id']
            || $plan->monitor_id != $attributes['monitor_id']
            || $plan->date->format('Y-m-d') != Carbon::parse($attributes['date'])->format('Y-m-d')
            || $plan->start_at->format('H:s') != $attributes['start_at']
            || $plan->end_at->format('H:s') != $attributes['end_at'];
    }

    /**
     * Validate the student's wallet and ensure sufficient balance.
     * @throws Exception
     */
    private function validateStudentAndWallet(Training $training, array $attributes): void
    {
        $currentHour = (int) ($attributes['previous_hour'] ?? $training->reservation->hour ?? 0);
        $newHour = (int) ($attributes['hour'] ?? $currentHour);
        $currentOfferId = $training->offer_id;
        $newOfferId = $attributes['offer_id'] ?? $currentOfferId;
        $studentChanged = $training->student_id !== $attributes['student_id'];
        $offerChanged = $currentOfferId !== $newOfferId;

        if ($studentChanged) {
            $this->validateStudentWallet($attributes['student_id'], $newOfferId, $newHour);

            $existingPlan = StudentInTrainingRepo::run(Arr::only($attributes, ['student_id', 'monitor_id', 'date', 'start_at', 'end_at']));
            if ($existingPlan) {
                throw new Exception(CodeErrorsEnum::UPDATE_PLANNING_EXISTE->value);
            }

            IncOrDecWalletRepo::run($training->student, $currentOfferId, 'inc', $currentHour);
            IncOrDecWalletRepo::run($attributes['student_id'], $newOfferId, WalletStatusEnum::DECREMENT->value, $newHour);
            return;
        }

        if ($offerChanged) {
            $this->validateStudentWallet($attributes['student_id'], $newOfferId, $newHour);
            IncOrDecWalletRepo::run($training->student, $currentOfferId, 'inc', $currentHour);
            IncOrDecWalletRepo::run($training->student, $newOfferId, WalletStatusEnum::DECREMENT->value, $newHour);
            return;
        }

        $hourDelta = $newHour - $currentHour;

        if ($hourDelta > 0) {
            $this->validateStudentWallet($attributes['student_id'], $newOfferId, $hourDelta);
            IncOrDecWalletRepo::run($training->student, $newOfferId, WalletStatusEnum::DECREMENT->value, $hourDelta);
            return;
        }

        if ($hourDelta < 0) {
            IncOrDecWalletRepo::run($training->student, $newOfferId, 'inc', abs($hourDelta));
        }
    }

    /**
     * Validate monitor availability for the new session.
     * @throws Exception
     */
    private function validateMonitorAvailability(bool $monitorHasChanged, array $attributes): void
    {
        if ($monitorHasChanged) {
            $existingPlan = MonitorInReservationRepo::run(Arr::only($attributes, ['monitor_id', 'date', 'start_at', 'end_at', 'id_not']));
            if ($existingPlan?->training) {

                throw new Exception(CodeErrorsEnum::UPDATE_PLANNING_EXISTE->value);
            }
        }
    }

    /**
     * Ensure the reservation does not already exist for the given student and monitor.
     * @throws Exception
     */
    private function validateUniquePlanning(array $attributes, string $trainingId): void
    {
        $attributes['id_not'] = $attributes['id_not'] ?? $trainingId;
        $existingPlan = StudentInTrainingRepo::run(Arr::only($attributes, ['student_id', 'monitor_id', 'date', 'start_at', 'end_at', 'id_not']));
        if ($existingPlan) {

            throw new Exception(CodeErrorsEnum::UPDATE_PLANNING_EXISTE->value);
        }
    }

    /**
     * Update or create a reservation.
     */
    private function updateOrCreateReservation(array $attributes, bool $monitorHasChanged, array $originalPlan): \Illuminate\Database\Eloquent\Builder|Model
    {
        $reservation = EditOrCreateReservationRepo::run(Arr::only($attributes, ['monitor_id', 'date', 'start_at', 'end_at', 'is_active', 'hour', 'color', 'lieu_id']));

        if ($monitorHasChanged) {
            EditOrCreateReservationRepo::run($originalPlan);
        }

        return $reservation;
    }

    /**
     * Send notifications to the monitor and student.
     */
    private function sendTrainingNotifications(string $studentId, Reservation $reservation): void
    {
        $monitorUser = $reservation?->monitor?->user;
        if ($monitorUser?->email) {
            $this->sendNotificationSafely(
                $monitorUser->email,
                new ValidationTrainingMonitorNotification($monitorUser, $reservation),
                'monitor training validation'
            );
        }

        $studentUser = Student::query()->find($studentId)?->user;
        if ($studentUser?->email) {
            $this->sendNotificationSafely(
                $studentUser->email,
                new ValidationTrainingStudentNotification($studentUser, $reservation),
                'student training validation'
            );
        }
    }

    private function sendTrainingUpdateNotifications(string $studentId, Reservation $reservation): void
    {
        $monitorUser = $reservation?->monitor?->user;
        if ($monitorUser?->email) {
            $this->sendNotificationSafely(
                $monitorUser->email,
                new ValidationTrainingMonitorNotification($monitorUser, $reservation),
                'monitor training update'
            );
        }

        $studentUser = Student::query()->find($studentId)?->user;
        if ($studentUser?->email) {
            $this->sendNotificationSafely(
                $studentUser->email,
                new UpdatedTrainingStudentNotification($studentUser, $reservation),
                'student training update'
            );
        }
    }

    private function sendContractUnlockedNotification(string $studentId, Reservation $reservation): void
    {
        $student = Student::query()->with('user', 'trainings.reservation')->find($studentId);
        $hasNotificationColumn = Schema::hasColumn('students', 'contract_available_notified_at');

        if (! $student || ! $student->user?->email || ($hasNotificationColumn && $student->contract_available_notified_at)) {
            return;
        }

        $bookedTrainingCount = $student->getBookedTrainingCount();
        $previousBookedTrainingCount = $bookedTrainingCount - 1;

        if ($previousBookedTrainingCount >= 2 || ! $student->canViewContract()) {
            return;
        }

        $this->sendNotificationSafely(
            $student->user->email,
            new ContractAvailableStudentNotification($student->user),
            'student contract available'
        );

        if ($hasNotificationColumn) {
            $student->update([
                'contract_available_notified_at' => now(),
            ]);
        }
    }

    private function sendNotificationSafely(string $email, object $notification, string $context): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $e) {
            Log::error('Training notification failed', [
                'context' => $context,
                'email' => $email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
