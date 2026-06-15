<?php
namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\PasswordHasherInterface;
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
            ->getPasswordHasher('common')
            ->hash($plainPassword);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return $this->factory
            ->getPasswordHasher('common')
            ->verify($hashedPassword, $plainPassword);
    }
}