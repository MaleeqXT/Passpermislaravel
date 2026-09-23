<?php

namespace App\Enums\V2\Student\Schedule\Offre;

enum OffreTypeEnum: int
{
    case FORFAIT = 1;
    case EXAMEN = 2;
    case CODE_ONLINE = 3;
}
