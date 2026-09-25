<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

/** AeroFlow must always have an administrator, or nobody could manage users. */
final class LastAdministratorException extends DomainException
{
    public static function cannotRemove(): self
    {
        return new self('This is the last administrator: assign the administrator role to someone else first.');
    }
}
