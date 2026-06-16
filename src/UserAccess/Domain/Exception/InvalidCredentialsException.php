<?php

namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public static function create(): self
    {
        return new self('Invalid email or password.');
    }
}
