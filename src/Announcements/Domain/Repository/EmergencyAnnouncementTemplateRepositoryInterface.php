<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Repository;

use App\Announcements\Domain\Entity\EmergencyAnnouncementTemplate;
use Symfony\Component\Uid\Uuid;

interface EmergencyAnnouncementTemplateRepositoryInterface
{
    public function save(EmergencyAnnouncementTemplate $template): void;

    public function findById(Uuid $id): ?EmergencyAnnouncementTemplate;

    /**
     * @return list<EmergencyAnnouncementTemplate>
     */
    public function findAll(?bool $active = null): array;
}
