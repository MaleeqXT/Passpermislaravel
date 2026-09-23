<?php

namespace App\Services\Student\Training\Cancellation;

use App\Models\Roles\Student\Schedule\Cancellation;
use App\Models\Roles\Student\Schedule\Training;
use Illuminate\Database\Eloquent\Model;

interface CancellationInterface
{

    /**
     * @param array $attributes
     * @return Training|null|Model
     */
    public function TryCancellation(array $attributes): Cancellation|null|Model;

    public function EditCancellation(Cancellation $cancellation, array $attributes): Cancellation|null|Model;

    public function CancellationTraining(Training $training): null|bool;
}
