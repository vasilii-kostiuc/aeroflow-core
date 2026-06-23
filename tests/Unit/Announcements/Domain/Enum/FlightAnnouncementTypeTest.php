<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Enum;

use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Enum\FlightDirection;
use PHPUnit\Framework\TestCase;

final class FlightAnnouncementTypeTest extends TestCase
{
    public function testMatchesAnnouncementTypesToLocalFlightDirection(): void
    {
        self::assertTrue(FlightAnnouncementType::Arrival->isCompatibleWith(FlightDirection::Arrival));
        self::assertFalse(FlightAnnouncementType::Arrival->isCompatibleWith(FlightDirection::Departure));
        self::assertTrue(FlightAnnouncementType::BoardingInvitation->isCompatibleWith(FlightDirection::Departure));
        self::assertFalse(FlightAnnouncementType::BoardingInvitation->isCompatibleWith(FlightDirection::Arrival));
    }
}
