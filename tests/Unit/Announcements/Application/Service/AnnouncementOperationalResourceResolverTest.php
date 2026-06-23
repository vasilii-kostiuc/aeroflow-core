<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Application\Service;

use App\Announcements\Application\Port\FlightOperations\OperationalResourceLookupInterface;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceSnapshot;
use App\Announcements\Application\Service\AnnouncementOperationalResourceResolver;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Exception\OperationalResourceUnavailableException;
use PHPUnit\Framework\TestCase;

final class AnnouncementOperationalResourceResolverTest extends TestCase
{
    public function testResolvesCheckInCountersInRequestedOrder(): void
    {
        $lookup = $this->createMock(OperationalResourceLookupInterface::class);
        $lookup->expects(self::once())
            ->method('resolveActiveCheckInCounters')
            ->with(['counter-1', 'counter-2'])
            ->willReturn([
                new OperationalResourceSnapshot('counter-1', '1'),
                new OperationalResourceSnapshot('counter-2', '2'),
            ]);

        $resources = new AnnouncementOperationalResourceResolver($lookup)->resolve(
            AnnouncementType::CheckInOpening,
            ['counter-1', 'counter-2'],
            null,
        );

        self::assertSame(
            [
                ['id' => 'counter-1', 'code' => '1'],
                ['id' => 'counter-2', 'code' => '2'],
            ],
            $resources->checkInCounterSnapshots(),
        );
        self::assertNull($resources->gate);
    }

    public function testRejectsDuplicatedCheckInCountersBeforeLookup(): void
    {
        $lookup = $this->createMock(OperationalResourceLookupInterface::class);
        $lookup->expects(self::never())->method('resolveActiveCheckInCounters');

        $this->expectException(OperationalResourceUnavailableException::class);

        new AnnouncementOperationalResourceResolver($lookup)->resolve(
            AnnouncementType::CheckInClosing,
            ['counter-1', 'counter-1'],
            null,
        );
    }

    public function testResolvesBoardingGate(): void
    {
        $gate = new OperationalResourceSnapshot('gate-1', 'A5');
        $lookup = $this->createMock(OperationalResourceLookupInterface::class);
        $lookup->expects(self::once())
            ->method('resolveActiveGate')
            ->with('gate-1')
            ->willReturn($gate);

        $resources = new AnnouncementOperationalResourceResolver($lookup)->resolve(
            AnnouncementType::BoardingInvitation,
            [],
            'gate-1',
        );

        self::assertSame(['id' => 'gate-1', 'code' => 'A5'], $resources->gateSnapshot());
        self::assertSame($gate, $resources->gate);
    }

    public function testArrivalDoesNotResolveOperationalResources(): void
    {
        $lookup = $this->createMock(OperationalResourceLookupInterface::class);
        $lookup->expects(self::never())->method('resolveActiveCheckInCounters');
        $lookup->expects(self::never())->method('resolveActiveGate');

        $resources = new AnnouncementOperationalResourceResolver($lookup)->resolve(
            AnnouncementType::Arrival,
            [],
            null,
        );

        self::assertSame([], $resources->checkInCounters);
        self::assertNull($resources->gate);
    }
}
