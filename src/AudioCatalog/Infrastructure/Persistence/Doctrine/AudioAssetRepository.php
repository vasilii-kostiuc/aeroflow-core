<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Persistence\Doctrine;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AudioAssetRepository implements AudioAssetRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AudioAsset $audioAsset): void
    {
        $this->entityManager->persist($audioAsset);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?AudioAsset
    {
        return $this->entityManager->find(AudioAsset::class, $id);
    }

    public function findActive(): array
    {
        return $this->entityManager->getRepository(AudioAsset::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        );
    }
}
