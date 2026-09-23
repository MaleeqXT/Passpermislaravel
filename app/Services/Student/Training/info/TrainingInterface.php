<?php

namespace App\Services\Student\Training\info;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Model;

interface TrainingInterface
{

    /**
     * @param array $attributes
     * @param Reservation| $reservation
     * @return Training|null|Model
     */
    public function create(array $attributes, Reservation $reservation): Training|null|Model;

    /**
     * @param Training $training
     * @param array $attributes
     * @return bool
     */
    public function update(Training $training, array $attributes): bool;
}
