<?php
namespace App\UserAccess\Domain\Repository;

use App\UserAccess\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findBuId(Uuid $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function remove(User $user): void;
}
