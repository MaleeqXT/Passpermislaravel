<?php

namespace App\Enums\V2\Student\Schedule\Wallet;

enum WalletBalanceTypeEnum: string
{
    case FULL = 'full';
    case INSTALLMENT = 'installment';
}
