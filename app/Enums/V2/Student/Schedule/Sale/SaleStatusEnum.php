<?php

namespace App\Enums\V2\Student\Schedule\Sale;

enum SaleStatusEnum: int
{
    case PENDING = 1;
    case PAID = 2;
    case REFUNDED = 3;
    case CANCELED = 4;
    case PARTIAL_REFUND = 5;

}
