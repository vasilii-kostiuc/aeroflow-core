<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LoginUser;

use App\Shared\Application\Event\DomainEventPublisher;
use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Event\UserLoggedIn;
use App\UserAccess\Domain\Exception\InvalidCredentialsException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private AuthTokenIssuer $authTokenIssuer,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(LoginUserCommand $command): LoginUserResult
    {
        $user = $this->userRepository->findByEmail($command->email);

        if ($user === null || !$this->passwordHasher->verify($command->password, (string) $user->getPassword())) {
            throw InvalidCredentialsException::create();
        }

        $tokenPair = $this->authTokenIssuer->issueFor($user);
        $this->events->publish(new UserLoggedIn((string) $user->getId(), (string) $user->getEmail()));

        return new LoginUserResult(
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
