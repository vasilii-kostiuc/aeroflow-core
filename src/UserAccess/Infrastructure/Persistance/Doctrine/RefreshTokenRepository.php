<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Persistance\Doctrine;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->em
            ->getRepository(RefreshToken::class)
            ->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function saveAll(array $refreshTokens): void
    {
        foreach ($refreshTokens as $refreshToken) {
            $this->em->persist($refreshToken);
        }

        $this->em->flush();
    }
}
