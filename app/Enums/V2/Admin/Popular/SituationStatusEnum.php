<?php

namespace App\Enums\V2\Admin\Popular;

enum SituationStatusEnum: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case INPROGRESS = 3;
}
