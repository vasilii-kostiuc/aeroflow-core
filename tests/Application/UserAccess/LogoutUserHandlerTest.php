<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess;

use App\Tests\Application\UserAccess\Support\InMemoryRefreshTokenRepository;
use App\Tests\Application\UserAccess\Support\PlainRefreshTokenHasher;
use App\Tests\Application\UserAccess\Support\RecordingMessageBus;
use App\UserAccess\Application\LogoutUser\LogoutUserCommand;
use App\UserAccess\Application\LogoutUser\LogoutUserHandler;
use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Event\UserLoggedOut;
use App\UserAccess\Domain\Exception\InvalidRefreshTokenException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LogoutUserHandlerTest extends TestCase
{
    public function testRevokesRefreshTokenAndDispatchesEvent(): void
    {
        $repository = new InMemoryRefreshTokenRepository();
        $token = RefreshToken::issue(
            User::register('dispatcher@example.com', 'hashed-password'),
            'hash-current-refresh-token',
            new DateTimeImmutable('+1 day'),
        );
        $repository->saveAll([$token]);
        $eventBus = new RecordingMessageBus();
        $handler = new LogoutUserHandler($repository, new PlainRefreshTokenHasher(), $eventBus);

        $handler(new LogoutUserCommand('current-refresh-token'));

        self::assertFalse($token->isActive(new DateTimeImmutable()));
        self::assertCount(1, $eventBus->messages);
        self::assertInstanceOf(UserLoggedOut::class, $eventBus->messages[0]);
    }

    public function testThrowsWhenRefreshTokenDoesNotExist(): void
    {
        $handler = new LogoutUserHandler(
            new InMemoryRefreshTokenRepository(),
            new PlainRefreshTokenHasher(),
            new RecordingMessageBus(),
        );

        $this->expectException(InvalidRefreshTokenException::class);

        $handler(new LogoutUserCommand('missing-refresh-token'));
    }
}
