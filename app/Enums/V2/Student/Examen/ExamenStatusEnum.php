<?php

namespace App\Enums\V2\Student\Examen;

enum ExamenStatusEnum: int
{
    case SUCCESS = 1;
    case FAILED = 2;
    case PENDING = 3;
}
