<?php

declare(strict_types=1);

namespace App\UserAccess\Application\CreateUser;

use App\Shared\Application\Event\DomainEventPublisher;
use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Application\Support\UserLoader;
use App\UserAccess\Application\UserResult;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Exception\UserAlreadyExistsException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateUserCommand $command): UserResult
    {
        $email = trim($command->email);
        if (null !== $this->users->findByEmail($email)) {
            throw UserAlreadyExistsException::withEmail($email);
        }

        $user = User::register($email, $this->passwordHasher->hash($command->password), UserLoader::role($command->role));
        $this->users->save($user);
        $this->events->publish(...$user->pullEvents());

        return UserResult::fromEntity($user);
    }
}
