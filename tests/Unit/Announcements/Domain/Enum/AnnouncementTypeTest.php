<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Enum;

use App\Announcements\Domain\Enum\AnnouncementType;
use PHPUnit\Framework\TestCase;

final class AnnouncementTypeTest extends TestCase
{
    public function testFlightTypesRequireFlightAndPlayAtPriority100(): void
    {
        foreach ([
            AnnouncementType::CheckInOpening,
            AnnouncementType::CheckInContinuation,
            AnnouncementType::CheckInClosing,
            AnnouncementType::BoardingInvitation,
            AnnouncementType::Arrival,
        ] as $type) {
            self::assertTrue($type->requiresFlight(), $type->value);
            self::assertSame(100, $type->playbackPriority(), $type->value);
        }
    }

    public function testEmergencyIsAirportWideAndPlaysAtThePreemptivePriority1000(): void
    {
        $type = AnnouncementType::Emergency;

        self::assertFalse($type->requiresFlight());
        self::assertFalse($type->requiresCheckInCounters());
        self::assertFalse($type->requiresGate());
        // The first slice has no automatic repeat while the mode lasts; the
        // dispatcher relaunches the preset by hand if needed.
        self::assertFalse($type->isRepeatable());
        self::assertSame(1000, $type->playbackPriority());
    }

    public function testSupplementaryIsAirportWideAndPlaysAtPriority50(): void
    {
        $type = AnnouncementType::Supplementary;

        self::assertFalse($type->requiresFlight());
        self::assertFalse($type->requiresCheckInCounters());
        self::assertFalse($type->requiresGate());
        self::assertFalse($type->isRepeatable());
        self::assertSame(50, $type->playbackPriority());
    }
}
