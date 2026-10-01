<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Support;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Exception\UserNotFoundException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/** Shared lookups of the user management use cases. */
final readonly class UserLoader
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function load(string $id): User
    {
        if (!Uuid::isValid($id)) {
            throw UserNotFoundException::withId($id);
        }

        return $this->users->findById(Uuid::fromString($id)) ?? throw UserNotFoundException::withId($id);
    }

    /** `admin`, `dispatcher`, or null / empty for no access. */
    public static function role(?string $code): ?UserRole
    {
        return null === $code || '' === $code ? null : UserRole::fromCode($code);
    }
}
