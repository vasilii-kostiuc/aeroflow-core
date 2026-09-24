<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\EmergencyAnnouncementTemplate;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class EmergencyAnnouncementTemplateRepository implements EmergencyAnnouncementTemplateRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(EmergencyAnnouncementTemplate $template): void
    {
        $this->entityManager->persist($template);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?EmergencyAnnouncementTemplate
    {
        return $this->entityManager->find(EmergencyAnnouncementTemplate::class, $id);
    }

    public function findAll(?bool $active = null): array
    {
        $criteria = $active === null ? [] : ['active' => $active];

        return $this->entityManager->getRepository(EmergencyAnnouncementTemplate::class)
            ->findBy($criteria, ['name' => 'ASC']);
    }
}
