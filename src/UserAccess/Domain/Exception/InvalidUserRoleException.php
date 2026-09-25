<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidUserRoleException extends DomainException
{
    public static function unknown(string $role): self
    {
        return new self(sprintf('Unknown role "%s". Allowed: admin, dispatcher, or none.', $role));
    }
}
