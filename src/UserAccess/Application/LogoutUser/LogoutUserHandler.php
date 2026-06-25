<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LogoutUser;

use App\Shared\Application\Event\DomainEventPublisher;
use App\UserAccess\Application\Security\RefreshTokenHasherInterface;
use App\UserAccess\Domain\Event\UserLoggedOut;
use App\UserAccess\Domain\Exception\InvalidRefreshTokenException;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class LogoutUserHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(LogoutUserCommand $command): void
    {
        $refreshToken = $this->refreshTokenRepository->findByTokenHash(
            $this->refreshTokenHasher->hash($command->refreshToken),
        );

        if ($refreshToken === null || !$refreshToken->isActive(new DateTimeImmutable())) {
            throw InvalidRefreshTokenException::create();
        }

        $refreshToken->revoke();
        $this->refreshTokenRepository->saveAll([$refreshToken]);

        $user = $refreshToken->getUser();
        $this->events->publish(new UserLoggedOut((string) $user->getId(), (string) $user->getEmail()));
    }
}
