<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User>
     */
    private array $usersByEmail = [];

    public function findById(Uuid $id): ?User
    {
        foreach ($this->usersByEmail as $user) {
            if ($user->getId()?->equals($id)) {
                return $user;
            }
        }

        return null;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->usersByEmail[$email] ?? null;
    }

    public function save(User $user): void
    {
        $this->usersByEmail[(string) $user->getEmail()] = $user;
    }

    public function remove(User $user): void
    {
        unset($this->usersByEmail[(string) $user->getEmail()]);
    }
}
