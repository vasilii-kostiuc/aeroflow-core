<?php

namespace App\UserAccess\Infrastructure\Persistance\Doctrine;

use App\UserAccess\Domain\Entity\User;
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
    }

    function findBuId(Uuid $id): ?User
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
    }
}
