<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Persistence\Doctrine;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final readonly class AudioFolderRepository implements AudioFolderRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AudioFolder $folder): void
    {
        $this->entityManager->persist($folder);
        $this->entityManager->flush();
    }

    public function remove(AudioFolder $folder): void
    {
        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?AudioFolder
    {
        return $this->entityManager->find(AudioFolder::class, $id);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(AudioFolder::class)->findBy([], ['nameKey' => 'ASC']);
    }

    public function findByParentAndNameKey(?Uuid $parentId, string $nameKey): ?AudioFolder
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(AudioFolder::class, 'f')
            ->where('f.nameKey = :nameKey')
            ->setParameter('nameKey', $nameKey)
            ->setMaxResults(1);

        if (null === $parentId) {
            $qb->andWhere('f.parentId IS NULL');
        } else {
            $qb->andWhere('f.parentId = :parentId')->setParameter('parentId', $parentId, UuidType::NAME);
        }

        $folder = $qb->getQuery()->getOneOrNullResult();

        return $folder instanceof AudioFolder ? $folder : null;
    }

    public function hasChildren(Uuid $id): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(AudioFolder::class, 'f')
            ->where('f.parentId = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
