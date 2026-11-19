<?php

namespace App\Services\Mail;

use RuntimeException;

class BrevoTransactionalMailException extends RuntimeException
{
    public function __construct(public readonly string $deliveryErrorCode)
    {
        parent::__construct('Transactional email delivery could not be completed.');
    }
}
