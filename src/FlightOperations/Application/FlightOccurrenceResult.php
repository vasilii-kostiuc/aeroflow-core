<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\FlightOccurrence;
use DateTimeImmutable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FlightOccurrenceResult',
    required: [
        'id',
        'flightDefinitionId',
        'source',
        'direction',
        'operationalDate',
        'sequenceNumber',
        'flightNumber',
        'originAirportCode',
        'destinationAirportCode',
        'status',
        'checkInCounters',
        'createdAt',
        'updatedAt',
    ],
)]
final readonly class FlightOccurrenceResult
{
    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     */
    public function __construct(
        public string $id,
        public string $flightDefinitionId,
        public string $source,
        public string $direction,
        public string $operationalDate,
        public int $sequenceNumber,
        public string $flightNumber,
        public string $originAirportCode,
        public string $destinationAirportCode,
        public string $status,
        public array $checkInCounters,
        public ?array $gate,
        public ?string $lastAnnouncementId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $completedAt,
        public ?string $cancelledAt,
    ) {
    }

    public static function fromEntity(FlightOccurrence $occurrence): self
    {
        return new self(
            id: $occurrence->getId()->toRfc4122(),
            flightDefinitionId: $occurrence->getFlightDefinitionId()->toRfc4122(),
            source: $occurrence->getSource()->value,
            direction: $occurrence->getDirection()->value,
            operationalDate: $occurrence->getOperationalDate()->format('Y-m-d'),
            sequenceNumber: $occurrence->getSequenceNumber(),
            flightNumber: $occurrence->getFlightNumberSnapshot(),
            originAirportCode: $occurrence->getOriginAirportCodeSnapshot(),
            destinationAirportCode: $occurrence->getDestinationAirportCodeSnapshot(),
            status: $occurrence->getStatus()->value,
            checkInCounters: $occurrence->getCheckInCounters(),
            gate: $occurrence->getGate(),
            lastAnnouncementId: $occurrence->getLastAnnouncementId()?->toRfc4122(),
            createdAt: $occurrence->getCreatedAt()->format(DATE_RFC3339),
            updatedAt: $occurrence->getUpdatedAt()->format(DATE_RFC3339),
            completedAt: $occurrence->getCompletedAt()?->format(DATE_RFC3339),
            cancelledAt: $occurrence->getCancelledAt()?->format(DATE_RFC3339),
        );
    }

    /**
     * @param array{
     *   id:string,
     *   flight_definition_id:string,
     *   source:string,
     *   direction:string,
     *   operational_date:string,
     *   sequence_number:int|string,
     *   flight_number_snapshot:string,
     *   origin_airport_code_snapshot:string,
     *   destination_airport_code_snapshot:string,
     *   status:string,
     *   check_in_counters:mixed,
     *   gate:mixed,
     *   last_announcement_id:string|null,
     *   created_at:string,
     *   updated_at:string,
     *   completed_at:string|null,
     *   cancelled_at:string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            flightDefinitionId: $row['flight_definition_id'],
            source: $row['source'],
            direction: $row['direction'],
            operationalDate: new DateTimeImmutable($row['operational_date'])->format('Y-m-d'),
            sequenceNumber: (int) $row['sequence_number'],
            flightNumber: $row['flight_number_snapshot'],
            originAirportCode: $row['origin_airport_code_snapshot'],
            destinationAirportCode: $row['destination_airport_code_snapshot'],
            status: $row['status'],
            checkInCounters: is_string($row['check_in_counters']) ? json_decode($row['check_in_counters'], true, 512, JSON_THROW_ON_ERROR) : $row['check_in_counters'],
            gate: is_string($row['gate']) ? json_decode($row['gate'], true, 512, JSON_THROW_ON_ERROR) : $row['gate'],
            lastAnnouncementId: $row['last_announcement_id'],
            createdAt: new DateTimeImmutable($row['created_at'])->format(DATE_RFC3339),
            updatedAt: new DateTimeImmutable($row['updated_at'])->format(DATE_RFC3339),
            completedAt: $row['completed_at'] === null ? null : new DateTimeImmutable($row['completed_at'])->format(DATE_RFC3339),
            cancelledAt: $row['cancelled_at'] === null ? null : new DateTimeImmutable($row['cancelled_at'])->format(DATE_RFC3339),
        );
    }
}
