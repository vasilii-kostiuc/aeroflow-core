<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidRefreshTokenException extends DomainException
{
    public static function create(): self
    {
        return new self('Invalid refresh token.');
    }
}
