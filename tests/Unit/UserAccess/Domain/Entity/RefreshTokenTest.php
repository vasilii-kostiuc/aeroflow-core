<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserAccess\Domain\Entity;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RefreshTokenTest extends TestCase
{
    public function testIssuedTokenIsActiveUntilExpiration(): void
    {
        $token = RefreshToken::issue(
            User::register('dispatcher@example.com', 'hashed-password'),
            'token-hash',
            new DateTimeImmutable('+1 day'),
        );

        self::assertSame('token-hash', $token->getTokenHash());
        self::assertTrue($token->isActive(new DateTimeImmutable()));
        self::assertFalse($token->isActive(new DateTimeImmutable('+2 days')));
    }

    public function testRevokeIsIdempotentAndCanStoreReplacementHash(): void
    {
        $token = RefreshToken::issue(
            User::register('dispatcher@example.com', 'hashed-password'),
            'token-hash',
            new DateTimeImmutable('+1 day'),
        );

        $token->revoke('replacement-hash');
        $revokedAt = $token->getRevokedAt();
        $token->revoke('ignored-replacement-hash');

        self::assertFalse($token->isActive(new DateTimeImmutable()));
        self::assertSame($revokedAt, $token->getRevokedAt());
        self::assertSame('replacement-hash', $token->getReplacedByHash());
    }
}
