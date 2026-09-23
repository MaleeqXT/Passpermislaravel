<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class RdvPermisApiException extends RuntimeException
{
    public function __construct(
        public readonly string $userMessage,
        public readonly int $responseStatus = 502,
        ?Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }
}
