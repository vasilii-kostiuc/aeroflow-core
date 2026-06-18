<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserAccess\Infrastructure\Persistance\Doctrine;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RefreshTokenRepositoryTest extends KernelTestCase
{
    public function testSavesAndFindsRefreshTokenByHash(): void
    {
        self::bootKernel();

        $userRepository = self::getContainer()->get(UserRepositoryInterface::class);
        $refreshTokenRepository = self::getContainer()->get(RefreshTokenRepositoryInterface::class);
        self::assertInstanceOf(UserRepositoryInterface::class, $userRepository);
        self::assertInstanceOf(RefreshTokenRepositoryInterface::class, $refreshTokenRepository);

        $user = User::register('refresh-token-'.uniqid('', true).'@example.com', 'hashed-password');
        $user->pullEvents();
        $userRepository->save($user);

        $refreshToken = RefreshToken::issue($user, hash('sha256', uniqid('', true)), new DateTimeImmutable('+1 day'));
        $refreshTokenRepository->saveAll([$refreshToken]);

        self::assertSame($refreshToken, $refreshTokenRepository->findByTokenHash($refreshToken->getTokenHash()));
    }
}
