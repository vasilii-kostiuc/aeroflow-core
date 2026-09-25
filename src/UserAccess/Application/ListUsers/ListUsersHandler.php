<?php

declare(strict_types=1);

namespace App\UserAccess\Application\ListUsers;

use App\UserAccess\Application\UserResult;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListUsersHandler
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    /**
     * @return list<UserResult>
     */
    public function __invoke(ListUsersQuery $query): array
    {
        return array_map(static fn (User $user): UserResult => UserResult::fromEntity($user), $this->users->findAll());
    }
}
