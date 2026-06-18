<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess;

use App\Tests\Application\UserAccess\Support\FakeAccessTokenIssuer;
use App\Tests\Application\UserAccess\Support\InMemoryRefreshTokenRepository;
use App\Tests\Application\UserAccess\Support\PlainRefreshTokenHasher;
use App\Tests\Application\UserAccess\Support\QueueRefreshTokenGenerator;
use App\UserAccess\Application\RefreshToken\RefreshTokenCommand;
use App\UserAccess\Application\RefreshToken\RefreshTokenHandler;
use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Exception\InvalidRefreshTokenException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RefreshTokenHandlerTest extends TestCase
{
    public function testRotatesActiveRefreshToken(): void
    {
        $repository = new InMemoryRefreshTokenRepository();
        $currentToken = RefreshToken::issue(
            User::register('dispatcher@example.com', 'hashed-password'),
            'hash-current-refresh-token',
            new DateTimeImmutable('+1 day'),
        );
        $repository->saveAll([$currentToken]);
        $handler = $this->createHandler($repository);

        $result = $handler(new RefreshTokenCommand('current-refresh-token'));

        self::assertSame('access-token-for-dispatcher@example.com', $result->accessToken);
        self::assertSame('next-refresh-token', $result->refreshToken);
        self::assertFalse($currentToken->isActive(new DateTimeImmutable()));
        self::assertSame('hash-next-refresh-token', $currentToken->getReplacedByHash());
        self::assertNotNull($repository->findByTokenHash('hash-next-refresh-token'));
    }

    public function testThrowsWhenRefreshTokenDoesNotExist(): void
    {
        $handler = $this->createHandler(new InMemoryRefreshTokenRepository());

        $this->expectException(InvalidRefreshTokenException::class);

        $handler(new RefreshTokenCommand('missing-refresh-token'));
    }

    public function testThrowsWhenRefreshTokenIsRevoked(): void
    {
        $repository = new InMemoryRefreshTokenRepository();
        $token = RefreshToken::issue(
            User::register('dispatcher@example.com', 'hashed-password'),
            'hash-current-refresh-token',
            new DateTimeImmutable('+1 day'),
        );
        $token->revoke();
        $repository->saveAll([$token]);
        $handler = $this->createHandler($repository);

        $this->expectException(InvalidRefreshTokenException::class);

        $handler(new RefreshTokenCommand('current-refresh-token'));
    }

    public function testThrowsWhenRefreshTokenIsExpired(): void
    {
        $repository = new InMemoryRefreshTokenRepository();
        $repository->saveAll([
            RefreshToken::issue(
                User::register('dispatcher@example.com', 'hashed-password'),
                'hash-current-refresh-token',
                new DateTimeImmutable('-1 day'),
            ),
        ]);
        $handler = $this->createHandler($repository);

        $this->expectException(InvalidRefreshTokenException::class);

        $handler(new RefreshTokenCommand('current-refresh-token'));
    }

    private function createHandler(InMemoryRefreshTokenRepository $repository): RefreshTokenHandler
    {
        return new RefreshTokenHandler(
            $repository,
            new PlainRefreshTokenHasher(),
            new AuthTokenIssuer(
                new FakeAccessTokenIssuer(),
                new QueueRefreshTokenGenerator(['next-refresh-token']),
                new PlainRefreshTokenHasher(),
                $repository,
                '+30 days',
            ),
        );
    }
}
