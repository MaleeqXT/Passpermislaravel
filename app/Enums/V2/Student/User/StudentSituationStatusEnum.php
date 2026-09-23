<?php

namespace App\Enums\V2\Student\User;

enum StudentSituationStatusEnum: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case INPROGRESS = 3;
}
