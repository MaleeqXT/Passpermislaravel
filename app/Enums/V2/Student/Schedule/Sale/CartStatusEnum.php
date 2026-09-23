<?php

namespace App\Enums\V2\Student\Schedule\Sale;

enum CartStatusEnum: int
{
    case PENDING = 1;
    case PAID = 2;
    case CANCELLED = 3;
    case REFUNDED = 4;
    case ABANDONED = 5;
    case EXPIRED = 6;
    case CPF = 7;
}
