<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

final readonly class DispatcherFlightOccurrenceResult
{
    public function __construct(
        public string $id,
        public string $flightDefinitionId,
        public string $flightNumber,
        public string $direction,
        public string $airportCode,
        public string $airportName,
        public string $operationalDate,
        public string $status,
        public bool $eligible,
        public ?string $unavailableReason,
        /** @var list<string> */
        public array $availableLanguages,
    ) {
    }
}
