<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Repository;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use Symfony\Component\Uid\Uuid;

interface FlightAnnouncementConfigRepositoryInterface
{
    public function save(FlightAnnouncementConfig $config): void;

    public function findById(Uuid $id): ?FlightAnnouncementConfig;

    /**
     * @return list<FlightAnnouncementConfig>
     */
    public function findByFlightDefinitionId(Uuid $flightDefinitionId): array;

    public function findOneForFlightAndType(Uuid $flightDefinitionId, FlightAnnouncementType $type): ?FlightAnnouncementConfig;
}
