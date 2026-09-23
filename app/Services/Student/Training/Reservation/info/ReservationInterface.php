<?php

namespace App\Services\Student\Training\Reservation\info;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;

interface ReservationInterface
{
    /**
     * @param array $attributes
     * @return Reservation
     */
    public function create(array $attributes);

    /**
     * @param Reservation $reservation
     * @param array $attributes
     * @return bool
     */
    public function update(Reservation $reservation, array $attributes): bool;

    public function delete(Reservation $reservation): bool;

    /**
     * @param array $attributes
     * @param Student $student
     * @return Reservation
     */
    public function getRandom(array $attributes, Student $student): Reservation;
}
