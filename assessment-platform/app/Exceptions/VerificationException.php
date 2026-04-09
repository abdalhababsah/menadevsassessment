<?php

namespace App\Exceptions;

use RuntimeException;

final class VerificationException extends RuntimeException
{
    public static function invalidToken(): self
    {
        return new self('The verification token is invalid.');
    }

    public static function alreadyVerified(): self
    {
        return new self('This email has already been verified.');
    }

    public static function expired(): self
    {
        return new self('The verification token has expired.');
    }
}
