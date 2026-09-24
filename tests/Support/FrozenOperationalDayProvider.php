<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\FlightOperations\Application\OperationalDay\OperationalDayProviderInterface;
use App\FlightOperations\Infrastructure\OperationalDay\AirportOperationalDayProvider;
use DateTimeImmutable;

/**
 * Operational day that a functional test can move, e.g. across midnight (task 027).
 *
 * The frozen day is static so it survives the kernel reboot the test client does
 * between requests. Unfrozen, it is the real airport day, so tests that do not
 * care keep working. A test that freezes must release in tearDown.
 */
final class FrozenOperationalDayProvider implements OperationalDayProviderInterface
{
    private static ?string $frozen = null;

    public function __construct(private readonly AirportOperationalDayProvider $airport)
    {
    }

    public static function freeze(string $date): void
    {
        self::$frozen = $date;
    }

    public static function release(): void
    {
        self::$frozen = null;
    }

    public function today(): DateTimeImmutable
    {
        return self::$frozen !== null ? new DateTimeImmutable(self::$frozen) : $this->airport->today();
    }
}
