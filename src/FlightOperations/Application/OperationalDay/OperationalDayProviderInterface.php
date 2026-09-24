<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\OperationalDay;

use DateTimeImmutable;

/**
 * The airport's current operational day (task 027).
 *
 * The operational date of a manual run is decided by the server from the airport
 * clock, never by the client: a dispatcher launches announcements now, and a
 * browser's clock or time zone is not an authority on which day "now" is.
 */
interface OperationalDayProviderInterface
{
    /** Today's operational date, as a date at midnight. */
    public function today(): DateTimeImmutable;
}
