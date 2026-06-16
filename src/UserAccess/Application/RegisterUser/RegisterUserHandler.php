<?php

declare(strict_types=1);

namespace App\UserAccess\Application\RegisterUser;

use App\UserAccess\Application\LoginUser\LoggedInUserResult;
use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Exception\UserAlreadyExistsException;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private AuthTokenIssuer $authTokenIssuer,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): RegisterUserResult
    {
        if ($this->userRepository->findByEmail($command->email) !== null) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user = User::register(
            email: $command->email,
            password: $this->passwordHasher->hash($command->password),
        );

        $this->userRepository->save($user);

        foreach ($user->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        $tokenPair = $this->authTokenIssuer->issueFor($user);

        return new RegisterUserResult(
            accessToken: $tokenPair->accessToken,
            refreshToken: $tokenPair->refreshToken,
            tokenType: $tokenPair->tokenType,
            expiresIn: $tokenPair->expiresIn,
            user: new LoggedInUserResult(
                id: (string) $user->getId(),
                email: (string) $user->getEmail(),
                roles: $user->getRoles(),
            ),
        );
    }
}
