<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Persistance\Doctrine;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function findById(Uuid $id): ?User
    {
        return $this->em->find(User::class, $id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em
            ->getRepository(User::class)
            ->findOneBy(['email' => (string) $email]);
    }

    public function remove(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    public function findAll(): array
    {
        return $this->em->getRepository(User::class)->findBy([], ['email' => 'ASC']);
    }

    /**
     * Counted in PHP: roles live in a JSON column and the user list is short
     * (tens of accounts), so a portable query is not worth a native one.
     */
    public function countByRole(UserRole $role): int
    {
        return count(array_filter(
            $this->findAll(),
            static fn (User $user): bool => $user->getRole() === $role,
        ));
    }
}
