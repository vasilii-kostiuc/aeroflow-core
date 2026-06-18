<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess;

use App\Tests\Application\UserAccess\Support\FakeAccessTokenIssuer;
use App\Tests\Application\UserAccess\Support\FakePasswordHasher;
use App\Tests\Application\UserAccess\Support\InMemoryRefreshTokenRepository;
use App\Tests\Application\UserAccess\Support\InMemoryUserRepository;
use App\Tests\Application\UserAccess\Support\PlainRefreshTokenHasher;
use App\Tests\Application\UserAccess\Support\QueueRefreshTokenGenerator;
use App\Tests\Application\UserAccess\Support\RecordingMessageBus;
use App\UserAccess\Application\RegisterUser\RegisterUserCommand;
use App\UserAccess\Application\RegisterUser\RegisterUserHandler;
use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Domain\Event\UserRegistered;
use App\UserAccess\Domain\Exception\UserAlreadyExistsException;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    public function testRegistersUserIssuesTokensAndDispatchesDomainEvent(): void
    {
        $userRepository = new InMemoryUserRepository();
        $refreshTokenRepository = new InMemoryRefreshTokenRepository();
        $eventBus = new RecordingMessageBus();
        $handler = $this->createHandler($userRepository, $refreshTokenRepository, $eventBus);

        $result = $handler(new RegisterUserCommand('dispatcher@example.com', 'password123'));

        $savedUser = $userRepository->findByEmail('dispatcher@example.com');
        self::assertNotNull($savedUser);
        self::assertSame('hashed-password123', $savedUser->getPassword());
        self::assertSame('access-token-for-dispatcher@example.com', $result->accessToken);
        self::assertSame('refresh-token', $result->refreshToken);
        self::assertSame('Bearer', $result->tokenType);
        self::assertSame(3600, $result->expiresIn);
        self::assertSame((string) $savedUser->getId(), $result->user->id);
        self::assertSame(['ROLE_USER'], $result->user->roles);
        self::assertCount(1, $refreshTokenRepository->all());

        self::assertCount(1, $eventBus->messages);
        self::assertInstanceOf(UserRegistered::class, $eventBus->messages[0]);
        self::assertSame((string) $savedUser->getId(), $eventBus->messages[0]->userId);
    }

    public function testThrowsWhenEmailAlreadyExists(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->save(\App\UserAccess\Domain\Entity\User::register('dispatcher@example.com', 'hashed-password'));
        $handler = $this->createHandler($userRepository, new InMemoryRefreshTokenRepository(), new RecordingMessageBus());

        $this->expectException(UserAlreadyExistsException::class);

        $handler(new RegisterUserCommand('dispatcher@example.com', 'password123'));
    }

    private function createHandler(
        UserRepositoryInterface $userRepository,
        InMemoryRefreshTokenRepository $refreshTokenRepository,
        RecordingMessageBus $eventBus,
    ): RegisterUserHandler {
        return new RegisterUserHandler(
            $userRepository,
            new FakePasswordHasher(),
            new AuthTokenIssuer(
                new FakeAccessTokenIssuer(),
                new QueueRefreshTokenGenerator(),
                new PlainRefreshTokenHasher(),
                $refreshTokenRepository,
                '+30 days',
            ),
            $eventBus,
        );
    }
}
