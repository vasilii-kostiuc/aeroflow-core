<?php
namespace App\UserAccess\Domain\Exception;

use App\Shared\Domain\DomainException;

final class UserAlreadyExistsException extends DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" already exists.', $email));
    }
}
