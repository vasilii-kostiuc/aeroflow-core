<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\FlightOperations\OperationalResourceSnapshot;

final readonly class AnnouncementOperationalResources
{
    /**
     * @param list<OperationalResourceSnapshot> $checkInCounters
     */
    public function __construct(
        public array $checkInCounters = [],
        public ?OperationalResourceSnapshot $gate = null,
    ) {
    }

    /** @return list<array{id:string,code:string}> */
    public function checkInCounterSnapshots(): array
    {
        return array_map(
            static fn (OperationalResourceSnapshot $counter): array => [
                'id' => $counter->id,
                'code' => $counter->code,
            ],
            $this->checkInCounters,
        );
    }

    /** @return array{id:string,code:string}|null */
    public function gateSnapshot(): ?array
    {
        return null === $this->gate
            ? null
            : ['id' => $this->gate->id, 'code' => $this->gate->code];
    }
}
