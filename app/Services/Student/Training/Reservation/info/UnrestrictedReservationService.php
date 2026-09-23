<?php

namespace App\Services\Student\Training\Reservation\info;

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

class UnrestrictedReservationService implements ReservationInterface
{

    /**
     * @param array $attributes
     * @return Reservation
     * @throws Exception
     */
    public function create(array $attributes)
    {
        // Skip the validation that checks for existing reservations
        // This allows multiple students to be assigned to the same monitor at the same time
        $this->deleteExistingReservation($attributes);
        return StoreReservationRepo::run($attributes);
    }

    /**
     * Check if the planning already exists.
     * DISABLED for unrestricted reservations - allows multiple students per monitor
     *
     * @param array $attributes
     * @throws Exception
     */
    protected function checkIfReservationExists(array $attributes): void
    {
        // This validation is disabled for unrestricted reservations
        // Multiple students can be assigned to the same monitor at the same time
        return;
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
        // Skip validation for unrestricted reservations
        return EditReservationRepo::run($reservation, $attributes);
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
            throw new Exception('Planning not found');
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
            throw new Exception('Planning has session');
        }
    }
}

