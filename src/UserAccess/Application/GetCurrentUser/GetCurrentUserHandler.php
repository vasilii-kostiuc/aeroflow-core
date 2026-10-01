<?php

declare(strict_types=1);

namespace App\UserAccess\Application\GetCurrentUser;

use App\UserAccess\Application\UserResult;
use App\UserAccess\Domain\Exception\UserNotFoundException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** The signed-in user with their current role — roles can change while a session lives. */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetCurrentUserHandler
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function __invoke(GetCurrentUserQuery $query): UserResult
    {
        $user = $this->users->findByEmail($query->email) ?? throw UserNotFoundException::withId($query->email);

        return UserResult::fromEntity($user);
    }
}
