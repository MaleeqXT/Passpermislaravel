<?php

namespace App\Exceptions;

use RuntimeException;

class RdvPermisOAuthException extends RuntimeException
{
    public function __construct(public readonly string $stage, string $message)
    {
        parent::__construct($message);
    }
}
