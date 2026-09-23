<?php

namespace App\Enums\V2\Student\Examen;

enum ExamenStatusForfaisEnum: int
{
    case HEUR_EVAL = 1;
    case ATTEND_NEPH = 2;
    case HEUR_EVAL_FORAIS = 3;
    case ACCES_CODE = 4;
    case NEPH = 5;
}
