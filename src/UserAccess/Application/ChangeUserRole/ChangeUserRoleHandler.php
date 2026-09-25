<?php

declare(strict_types=1);

namespace App\UserAccess\Application\ChangeUserRole;

use App\Shared\Application\Event\DomainEventPublisher;
use App\UserAccess\Application\Support\UserLoader;
use App\UserAccess\Application\UserResult;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Exception\LastAdministratorException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Grants or removes a role. AeroFlow always keeps an administrator: taking the
 * role away from the last one is refused, otherwise nobody could manage users.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeUserRoleHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserLoader $loader,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(ChangeUserRoleCommand $command): UserResult
    {
        $user = $this->loader->load($command->userId);
        $role = UserLoader::role($command->role);

        if ($user->isAdmin() && UserRole::Admin !== $role && $this->users->countByRole(UserRole::Admin) <= 1) {
            throw LastAdministratorException::cannotRemove();
        }

        if ($user->changeRole($role)) {
            $this->users->save($user);
            $this->events->publish(...$user->pullEvents());
        }

        return UserResult::fromEntity($user);
    }
}
