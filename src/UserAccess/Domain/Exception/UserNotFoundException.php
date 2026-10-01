<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

final class UserNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('User "%s" was not found.', $id));
    }
}
