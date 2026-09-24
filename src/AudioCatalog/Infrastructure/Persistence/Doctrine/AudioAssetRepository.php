<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Persistence\Doctrine;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Enum\AudioAssetSource;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
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
        // Case-insensitive: the database collation may be byte order (musl), which
        // would put every capitalised title before every lowercase one.
        /** @var list<AudioAsset> $assets */
        $assets = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AudioAsset::class, 'a')
            ->where('a.active = true')
            ->orderBy('LOWER(a.title)', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $assets;
    }

    public function findActiveGeneratedByContent(string $textHash, string $languageCode, string $voice): array
    {
        return array_values($this->entityManager->getRepository(AudioAsset::class)->findBy([
            'source' => AudioAssetSource::Generated,
            'active' => true,
            'ttsTextHash' => $textHash,
            'languageCode' => $languageCode,
            'ttsVoice' => $voice,
        ], ['createdAt' => 'DESC']));
    }

    public function countActiveInFolder(Uuid $folderId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(AudioAsset::class, 'a')
            ->where('a.folderId = :folderId')
            ->andWhere('a.active = true')
            ->setParameter('folderId', $folderId, UuidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findInactiveInFolder(Uuid $folderId): array
    {
        /** @var list<AudioAsset> $assets */
        $assets = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AudioAsset::class, 'a')
            ->where('a.folderId = :folderId')
            ->andWhere('a.active = false')
            ->setParameter('folderId', $folderId, UuidType::NAME)
            ->getQuery()
            ->getResult();

        return $assets;
    }
}
