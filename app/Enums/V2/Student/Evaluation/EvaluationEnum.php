<?php

namespace App\Enums\V2\Student\Evaluation;

enum EvaluationEnum: int
{
    case INPROGRESS = 1;
    case ACTIVE = 2;
    case INACTIVE = 3;
}
