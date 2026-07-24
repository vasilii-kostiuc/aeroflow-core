<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class SupplementaryAnnouncementTemplateRepository implements SupplementaryAnnouncementTemplateRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(SupplementaryAnnouncementTemplate $template): void
    {
        $this->entityManager->persist($template);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?SupplementaryAnnouncementTemplate
    {
        return $this->entityManager->find(SupplementaryAnnouncementTemplate::class, $id);
    }

    public function findAll(?bool $active = null): array
    {
        $criteria = $active === null ? [] : ['active' => $active];

        return $this->entityManager->getRepository(SupplementaryAnnouncementTemplate::class)
            ->findBy($criteria, ['name' => 'ASC']);
    }
}
