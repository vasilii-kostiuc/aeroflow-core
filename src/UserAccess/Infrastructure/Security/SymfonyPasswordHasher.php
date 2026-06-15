<?php
namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $factory,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->factory
            ->getPasswordHasher(User::class)
            ->hash($plainPassword);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return $this->factory
            ->getPasswordHasher(User::class)
            ->verify($hashedPassword, $plainPassword);
    }
}
