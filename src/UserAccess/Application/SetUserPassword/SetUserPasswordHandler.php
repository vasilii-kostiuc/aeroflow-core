<?php

declare(strict_types=1);

namespace App\UserAccess\Application\SetUserPassword;

use App\Shared\Application\Event\DomainEventPublisher;
use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Application\Support\UserLoader;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * An administrator sets a user's password (there is no e-mail recovery). The
 * user's refresh tokens are revoked, so an old session does not outlive the
 * password it was opened with; the short-lived access token simply expires.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class SetUserPasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RefreshTokenRepositoryInterface $refreshTokens,
        private PasswordHasherInterface $passwordHasher,
        private UserLoader $loader,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(SetUserPasswordCommand $command): void
    {
        $user = $this->loader->load($command->userId);
        $user->changePassword($this->passwordHasher->hash($command->password));
        $this->users->save($user);

        $tokens = $this->refreshTokens->findNotRevokedByUser($user);
        foreach ($tokens as $token) {
            $token->revoke();
        }
        $this->refreshTokens->saveAll($tokens);

        $this->events->publish(...$user->pullEvents());
    }
}
