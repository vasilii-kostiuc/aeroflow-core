<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Repository;

use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use Symfony\Component\Uid\Uuid;

interface SupplementaryAnnouncementTemplateRepositoryInterface
{
    public function save(SupplementaryAnnouncementTemplate $template): void;

    public function findById(Uuid $id): ?SupplementaryAnnouncementTemplate;

    /**
     * @return list<SupplementaryAnnouncementTemplate>
     */
    public function findAll(?bool $active = null): array;
}
