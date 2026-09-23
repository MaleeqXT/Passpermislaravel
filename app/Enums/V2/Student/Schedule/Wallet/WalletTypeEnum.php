<?php

namespace App\Enums\V2\Student\Schedule\Wallet;

enum WalletTypeEnum: int
{
    case ACTIVE = 1;
    case PENDING = 2;
    case INACTIVE = 3;
}
