<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\FlightOperations\OperationalResourceLookupInterface;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Exception\OperationalResourceUnavailableException;

final readonly class AnnouncementOperationalResourceResolver
{
    public function __construct(private OperationalResourceLookupInterface $resources)
    {
    }

    /**
     * @param list<string> $checkInCounterIds
     */
    public function resolve(
        AnnouncementType $type,
        array $checkInCounterIds,
        ?string $gateId,
    ): AnnouncementOperationalResources {
        if ($type->requiresCheckInCounters()) {
            if ([] === $checkInCounterIds || count($checkInCounterIds) !== count(array_unique($checkInCounterIds))) {
                throw OperationalResourceUnavailableException::counters($checkInCounterIds);
            }

            $counters = $this->resources->resolveActiveCheckInCounters($checkInCounterIds);
            if (count($counters) !== count($checkInCounterIds)) {
                throw OperationalResourceUnavailableException::counters($checkInCounterIds);
            }

            return new AnnouncementOperationalResources(checkInCounters: $counters);
        }

        if ($type->requiresGate()) {
            if (null === $gateId || null === ($gate = $this->resources->resolveActiveGate($gateId))) {
                throw OperationalResourceUnavailableException::gate((string) $gateId);
            }

            return new AnnouncementOperationalResources(gate: $gate);
        }

        return new AnnouncementOperationalResources();
    }
}
