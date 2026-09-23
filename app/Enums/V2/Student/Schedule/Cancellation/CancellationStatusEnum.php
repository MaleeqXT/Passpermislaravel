<?php

namespace App\Enums\V2\Student\Schedule\Cancellation;

enum CancellationStatusEnum: int
{
    case PENDING = 1;
    case REFUSED = 2;
    case SUCCESS_CANCELLED = 3;
}
