<?php

namespace App\Enums\V2\Student\Schedule\Sale;

enum SalePaymentMethodEnum: int
{
    case  STRIP = 1;
    case  PAYPAL = 2;
    case  CASH = 3;
    case  TRANSFER = 4;
    case  CHECK = 5;

    public function label(): string
    {
        return match ($this) {
            self::STRIP => 'Carte bancaire (Stripe)',
            self::PAYPAL => 'PayPal',
            self::CASH => 'Espèces',
            self::TRANSFER => 'Virement bancaire',
            self::CHECK => 'Chèque',
        };
    }
}
