<?php

namespace App\Services\Student\Training\Reservation\info;

use App\Enums\V2\Admin\Popular\CodeErrorsEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\DestroyMonitorReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\DestroyReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\EditReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchAllReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\FetchRandomReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\StoreReservationRepo;
use Exception;
use Illuminate\Support\Arr;

class ReservationService implements ReservationInterface
{

    /**
     * @param array $attributes
     * @return Reservation
     * @throws Exception
     */
    public function create(array $attributes)
    {
        // Keep validation for create - one student per monitor per time
        $this->checkIfReservationExists($attributes);
        $this->deleteExistingReservation($attributes);
        return StoreReservationRepo::run($attributes);
    }

    /**
     * Check if the planning already exists.
     *
     * @param array $attributes
     * @throws Exception
     */
    protected function checkIfReservationExists(array $attributes): void
    {
        $reservations = FetchAllReservationRepo::run(Arr::except($attributes, 'is_active'))->count();
        if ($reservations > 0) {
            throw new Exception(CodeErrorsEnum::CREATE_PLANNING_EXISTE->value);
        }
    }

    /**
     * Delete any existing reservation if planning has availability.
     *
     * @param array $attributes
     */
    protected function deleteExistingReservation(array $attributes): void
    {
        DestroyMonitorReservationRepo::run($attributes);
    }

    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public function update(Reservation $reservation, array $attributes): bool
    {
        // Check for conflicts before updating - ensure no overlapping reservations
        $this->checkForUpdateConflicts($reservation, $attributes);

        return EditReservationRepo::run($reservation, $attributes);
    }

    /**
     * Check if the update will cause conflicts with existing reservations.
     * Always check conflicts before updating.
     * Only block if it's a DIFFERENT student trying to book the same time.
     *
     * @param Reservation $reservation
     * @param array $attributes
     * @throws Exception
     */
    protected function checkForUpdateConflicts(Reservation $reservation, array $attributes): void
    {
        $monitor_id = $attributes['monitor_id'] ?? $reservation->monitor_id;
        $date = $attributes['date'] ?? $reservation->date;
        $start_at = $attributes['start_at'] ?? $reservation->start_at;
        $end_at = $attributes['end_at'] ?? $reservation->end_at;

        // Get the current student ID if the reservation has training
        $currentStudentId = $reservation->training?->student_id;

        if (!$currentStudentId) {
            // If no student associated, skip conflict check
            return;
        }

        // Check for conflicting reservations with DIFFERENT students (excluding the current one)
        $conflicts = Reservation::query()
            ->where('monitor_id', $monitor_id)
            ->whereDate('date', $date)
            ->where('id', '!=', $reservation->id)
            ->whereHas('training', function ($query) use ($currentStudentId) {
                // Only check if it's a different student
                if ($currentStudentId) {
                    $query->where('student_id', '!=', $currentStudentId);
                }
            })
            ->where('start_at', '<', $end_at)
            ->where('end_at', '>', $start_at)
            ->exists();

        if ($conflicts) {
            throw new Exception('La réservation ou la disponibilité existe déjà.');
        }
    }

    /**
     * @param array $attributes
     * @param Student $student
     * @return Reservation
     * @throws Exception
     */
    public function getRandom(array $attributes, Student $student): Reservation
    {

        $reservation = FetchRandomReservationRepo::run($attributes, $student);

        if (empty($reservation)) {
            throw new Exception(CodeErrorsEnum::PLANNING_NOT_FOUND->value);
        }

        return $reservation;
    }

    /**
     * @throws Exception
     */
    public function delete(Reservation $reservation): bool
    {
        $this->tryIfResrvationHasTraining($reservation);

        return DestroyReservationRepo::run($reservation);
    }

    /**
     * Check if the planning has a session associated.
     *
     * @param Reservation $reservation
     * @throws Exception
     */
    protected function tryIfResrvationHasTraining(Reservation $reservation): void
    {
        $existePlanning = Reservation::query()->where('id', $reservation->id)
            ->doesntHave('training')->exists();
        if (!$existePlanning) {
            throw new Exception(CodeErrorsEnum::PLANNING_HAS_SESSION->value);
        }
    }
}
