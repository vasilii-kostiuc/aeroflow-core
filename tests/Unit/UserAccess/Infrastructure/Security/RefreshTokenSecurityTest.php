<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserAccess\Infrastructure\Security;

use App\UserAccess\Infrastructure\Security\RandomRefreshTokenGenerator;
use App\UserAccess\Infrastructure\Security\Sha256RefreshTokenHasher;
use PHPUnit\Framework\TestCase;

final class RefreshTokenSecurityTest extends TestCase
{
    public function testSha256HasherReturnsStableHash(): void
    {
        $hasher = new Sha256RefreshTokenHasher();

        self::assertSame(hash('sha256', 'plain-token'), $hasher->hash('plain-token'));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hasher->hash('plain-token'));
    }

    public function testRandomGeneratorReturnsNonEmptyDifferentTokens(): void
    {
        $generator = new RandomRefreshTokenGenerator();

        $first = $generator->generate();
        $second = $generator->generate();

        self::assertNotSame('', $first);
        self::assertNotSame($first, $second);
    }
}
