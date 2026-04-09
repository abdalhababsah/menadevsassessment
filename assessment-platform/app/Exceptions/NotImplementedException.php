<?php

namespace App\Exceptions;

use RuntimeException;

final class NotImplementedException extends RuntimeException
{
    public function __construct(string $message = 'This feature has not been implemented yet.')
    {
        parent::__construct($message);
    }
}
