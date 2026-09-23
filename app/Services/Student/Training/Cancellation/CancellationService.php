<?php

namespace App\Services\Student\Training\Cancellation;


use App\Models\Roles\Student\Schedule\Cancellation;
use App\Models\Roles\Student\Schedule\Training;
use App\Notifications\V1\Monitor\Training\Cancellation\CancellationTrainingMonitorNotification;
use App\Notifications\V1\Student\Training\Cancellation\CancellationTrainingStudentNotification;
use App\Notifications\V1\Student\Training\Cancellation\CancellationRefusedStudentNotification;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\DestroyReviewRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\EditCancellationRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\FetchCanceledTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Cancellation\StoreCancellationRepo;
use App\Repository\V2\Student\Schedule\Training\Job\DestroyTrainingRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CancellationService implements CancellationInterface
{

    /**
     * @param array $attributes
     * @return Cancellation|null|Model
     * @throws Exception
     */
    public function TryCancellation(array $attributes): Cancellation|null|Model
    {
        $canceledPerDay = $this->checkCancellationPerDay($attributes['training_id']);
        if (!$canceledPerDay) {
            return $this->flashError("Cette session ne peut pas être annulée.");
        }

        $canceledPerThreeDay = $this->checkCancellationPerThreeDays($attributes['training_id']);
        if ($canceledPerThreeDay) {
            return $this->handleImmediateCancellation($attributes['training_id']);
        }

        $annulation = $this->storeCancellation($attributes);
        session()->flash('success', "Nous avons bien reçu votre demande d'annulation.");
        return $annulation;
    }

    /**
     * @param string $trainingId
     * @return bool
     */
    protected function checkCancellationPerDay(string $trainingId): bool
    {

        return (bool)FetchCanceledTrainingRepo::run(['training_id' => $trainingId]);
    }

    /**
     * @param string $trainingId
     * @return bool
     */
    protected function checkCancellationPerThreeDays(string $trainingId): bool
    {
        return (bool)FetchCanceledTrainingRepo::run(['training_id' => $trainingId], 2);
    }

    /**
     * Handle immediate cancellation of a session.
     *
     * @param string $trainingId
     * @return Model|null
     * @throws Exception
     */
    protected function handleImmediateCancellation(string $trainingId): ?Model
    {
        $training = Training::query()->findOrFail($trainingId);
        DestroyReviewRepo::run($training->reservation);
        IncOrDecWalletRepo::run($training->student, $training->offer_id, 'inc', $training->reservation?->hour ?? 1);

        $this->sendCancellationEmails($training);
        DestroyTrainingRepo::run($trainingId);

        session()->flash('success', "Annulation de la session effectuée avec succès.");
        return null;
    }

    /**
     * Store a new cancellation record.
     *
     * @param array $attributes
     * @return Cancellation|null|Model
     * @throws Exception
     */
    protected function storeCancellation(array $attributes): Cancellation|null|Model
    {
        return StoreCancellationRepo::run($attributes);
    }

    /**
     * Send cancellation emails.
     *
     * @param Training $training
     * @return void
     */
    protected function sendCancellationEmails(Training $training): void
    {
        $reservation = $training->reservation;

        // Add logging for debugging
        \Log::info('Sending cancellation emails for training ID: ' . $training->id);

        // Notify monitor
        $monitorUser = $reservation?->monitor?->user;
        if ($monitorUser?->email) {
            \Log::info('Sending cancellation email to monitor: ' . $monitorUser->email);
            $this->sendNotificationSafely(
                $monitorUser->email,
                new CancellationTrainingMonitorNotification($monitorUser, $reservation),
                'monitor cancellation'
            );
        } else {
            \Log::warning('No monitor user found for reservation ID: ' . $reservation?->id);
        }

        // Notify student
        $studentUser = $training->student?->user;
        if ($studentUser?->email) {
            \Log::info('Sending cancellation email to student: ' . $studentUser->email);
            $this->sendNotificationSafely(
                $studentUser->email,
                new CancellationTrainingStudentNotification($studentUser, $reservation),
                'student cancellation'
            );
        } else {
            \Log::warning('No student user found for training ID: ' . $training->id);
        }

        \Log::info('Finished sending cancellation emails for training ID: ' . $training->id);
    }

    /**
     * Flash an error message and return null.
     *
     * @param string $message
     * @return null
     */
    protected function flashError(string $message): ?Model
    {
        session()->flash('error', $message);
        return null;
    }

    /**
     * Update a late cancellation request.
     *
     * @param Cancellation $cancellation
     * @param array $attributes
     * @return Cancellation|null|Model
     * @throws Exception
     */
    public function EditCancellation(Cancellation $cancellation, array $attributes): Cancellation|null|Model
    {
        EditCancellationRepo::run($cancellation, $attributes);
        if ($attributes['status'] == 2) {
            $studentUser = $cancellation->training->student->user;
            if ($studentUser?->email) {
                $this->sendNotificationSafely(
                    $studentUser->email,
                    new CancellationRefusedStudentNotification($studentUser, $cancellation->training->reservation),
                    'student cancellation refused'
                );
            }
            return $cancellation->refresh();
        }
        if ($cancellation->is_justified) {
            return $this->flashError("Cette leçon est déjà marquée comme justifiée.");
        }
        $this->processLateCancellation($cancellation, $attributes);
        return $cancellation->refresh();
    }

    /**
     * Process a late cancellation.
     *
     * @param Cancellation $cancellation
     * @param array $attributes
     * @return void
     * @throws Exception
     */
    protected function processLateCancellation(Cancellation $cancellation, array $attributes)
    {
        $training = $cancellation->training;

        // check training is empty 
        // if (!$training?->reservation) {
        //     return $this->flashError("Cette leçon est déjà annulée ou n'existe pas.");
        // }
        $this->sendCancellationEmails($training);

        IncOrDecWalletRepo::run($training?->student, $training->offer_id, 'inc', $training?->reservation?->hour);

        $cancellation->training()->delete();
    }

    /**
     * Cancel a session.
     *
     * @param Training $training
     * @return bool
     * @throws Exception
     */
    public function CancellationTraining(Training $training): bool
    {
        IncOrDecWalletRepo::run($training->student, $training->offer_id, 'inc', $training->reservation->hour);
        $this->sendCancellationEmails($training);

        DestroyTrainingRepo::run($training);
        session()->flash('success', "Annulation de la session confirmée.");
        return true;
    }
    protected function sendNotificationSafely(string $email, object $notification, string $context): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $e) {
            Log::error('Cancellation notification failed', [
                'context' => $context,
                'email' => $email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
