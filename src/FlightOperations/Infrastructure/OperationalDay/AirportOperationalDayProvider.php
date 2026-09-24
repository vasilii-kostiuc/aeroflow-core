<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\OperationalDay;

use App\FlightOperations\Application\OperationalDay\OperationalDayProviderInterface;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Operational day = the calendar date on the airport's wall clock (AIRPORT_TIMEZONE).
 * The day boundary is midnight; a run started before it is carried over by the
 * dispatcher board rather than by shifting the boundary.
 */
final readonly class AirportOperationalDayProvider implements OperationalDayProviderInterface
{
    private DateTimeZone $timezone;

    public function __construct(
        #[Autowire(env: 'AIRPORT_TIMEZONE')]
        string $timezone,
    ) {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function today(): DateTimeImmutable
    {
        $localDate = new DateTimeImmutable('now', $this->timezone)->format('Y-m-d');

        return new DateTimeImmutable($localDate);
    }
}
