<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess;

use App\Tests\Application\UserAccess\Support\FakeAccessTokenIssuer;
use App\Tests\Application\UserAccess\Support\FakePasswordHasher;
use App\Tests\Application\UserAccess\Support\InMemoryRefreshTokenRepository;
use App\Tests\Application\UserAccess\Support\InMemoryUserRepository;
use App\Tests\Application\UserAccess\Support\PlainRefreshTokenHasher;
use App\Tests\Application\UserAccess\Support\QueueRefreshTokenGenerator;
use App\Tests\Support\RecordingEventPublisher;
use App\UserAccess\Application\LoginUser\LoginUserCommand;
use App\UserAccess\Application\LoginUser\LoginUserHandler;
use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Event\UserLoggedIn;
use App\UserAccess\Domain\Exception\InvalidCredentialsException;
use PHPUnit\Framework\TestCase;

final class LoginUserHandlerTest extends TestCase
{
    public function testLogsUserInAndDispatchesEvent(): void
    {
        $userRepository = new InMemoryUserRepository();
        $user = User::register('dispatcher@example.com', 'hashed-password123');
        $user->pullEvents();
        $userRepository->save($user);
        $eventBus = new RecordingEventPublisher();

        $handler = new LoginUserHandler(
            $userRepository,
            new FakePasswordHasher(),
            new AuthTokenIssuer(
                new FakeAccessTokenIssuer(),
                new QueueRefreshTokenGenerator(),
                new PlainRefreshTokenHasher(),
                new InMemoryRefreshTokenRepository(),
                '+30 days',
            ),
            $eventBus,
        );

        $result = $handler(new LoginUserCommand('dispatcher@example.com', 'password123'));

        self::assertSame('access-token-for-dispatcher@example.com', $result->accessToken);
        self::assertSame('refresh-token', $result->refreshToken);
        self::assertSame((string) $user->getId(), $result->user->id);
        self::assertCount(1, $eventBus->messages);
        self::assertInstanceOf(UserLoggedIn::class, $eventBus->messages[0]);
    }

    public function testThrowsWhenUserDoesNotExist(): void
    {
        $handler = $this->createHandler(new InMemoryUserRepository());

        $this->expectException(InvalidCredentialsException::class);

        $handler(new LoginUserCommand('missing@example.com', 'password123'));
    }

    public function testThrowsWhenPasswordIsInvalid(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->save(User::register('dispatcher@example.com', 'hashed-password123'));
        $handler = $this->createHandler($userRepository);

        $this->expectException(InvalidCredentialsException::class);

        $handler(new LoginUserCommand('dispatcher@example.com', 'wrong-password'));
    }

    private function createHandler(InMemoryUserRepository $userRepository): LoginUserHandler
    {
        return new LoginUserHandler(
            $userRepository,
            new FakePasswordHasher(),
            new AuthTokenIssuer(
                new FakeAccessTokenIssuer(),
                new QueueRefreshTokenGenerator(),
                new PlainRefreshTokenHasher(),
                new InMemoryRefreshTokenRepository(),
                '+30 days',
            ),
            new RecordingEventPublisher(),
        );
    }
}
