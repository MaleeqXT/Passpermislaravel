<?php

namespace App\Enums\V2\Monitor;

enum MonitorSituationStatusEnum: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case INPROGRESS = 3;
}
