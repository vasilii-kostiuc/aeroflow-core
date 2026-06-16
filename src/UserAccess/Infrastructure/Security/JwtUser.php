<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Security;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class JwtUser implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $email,
        private array $roles,
    ) {
        if ($this->email === '') {
            throw new InvalidArgumentException('JWT user email cannot be empty.');
        }
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
