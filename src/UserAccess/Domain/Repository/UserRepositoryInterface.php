<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Repository;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    public function remove(User $user): void;

    /**
     * @return list<User> ordered by email
     */
    public function findAll(): array;

    public function countByRole(UserRole $role): int;
}
