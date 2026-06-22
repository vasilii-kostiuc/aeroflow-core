<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Infrastructure\DataFixtures;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Infrastructure\DataFixtures\FlightDefinitionFixtures;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

final class FlightDefinitionFixturesTest extends TestCase
{
    public function testItCreatesRepresentativeChisinauFlights(): void
    {
        $repository = $this->createMock(FlightDefinitionRepositoryInterface::class);
        $repository
            ->expects(self::exactly(24))
            ->method('hasConflictingDefinition')
            ->willReturn(false);

        $persisted = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects(self::exactly(24))
            ->method('persist')
            ->willReturnCallback(static function (object $object) use (&$persisted): void {
                $persisted[] = $object;
            });
        $manager->expects(self::once())->method('flush');

        new FlightDefinitionFixtures($repository)->load($manager);

        self::assertCount(24, $persisted);
        self::assertContainsOnlyInstancesOf(FlightDefinition::class, $persisted);

        $departures = 0;
        $arrivals = 0;

        foreach ($persisted as $definition) {
            self::assertSame([], $definition->pullEvents());

            if ($definition->getDirection()->value === 'departure') {
                ++$departures;
                self::assertSame('RMO', $definition->getOriginAirportCode()->toString());
            } else {
                ++$arrivals;
                self::assertSame('RMO', $definition->getDestinationAirportCode()->toString());
            }
        }

        self::assertSame(12, $departures);
        self::assertSame(12, $arrivals);
    }

    public function testItSkipsExistingDefinitions(): void
    {
        $repository = $this->createMock(FlightDefinitionRepositoryInterface::class);
        $repository
            ->expects(self::exactly(24))
            ->method('hasConflictingDefinition')
            ->willReturn(true);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::once())->method('flush');

        new FlightDefinitionFixtures($repository)->load($manager);
    }

    public function testItHasDedicatedFixtureGroup(): void
    {
        self::assertSame(['flight-operations'], FlightDefinitionFixtures::getGroups());
    }
}
