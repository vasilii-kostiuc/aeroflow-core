<?php

declare(strict_types=1);

namespace App\Tests\Integration\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\Dispatcher\DispatcherFlightOccurrenceQueryInterface;
use App\FlightOperations\Application\Dispatcher\DispatcherFlightOccurrenceResult;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/** Board of an operational day: one current run per card, in-progress runs carried over (task 027). */
final class DbalDispatcherBoardQueryTest extends KernelTestCase
{
    private const TODAY = '2031-03-15';
    private const YESTERDAY = '2031-03-14';
    private const TWO_DAYS_AGO = '2031-03-13';

    private FlightDefinitionRepositoryInterface $definitions;
    private FlightOccurrenceRepositoryInterface $occurrences;

    public function testSelectsTheCurrentRunOfEachCard(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->definitions = $container->get(FlightDefinitionRepositoryInterface::class);
        $this->occurrences = $container->get(FlightOccurrenceRepositoryInterface::class);
        $query = $container->get(DispatcherFlightOccurrenceQueryInterface::class);
        self::assertInstanceOf(DispatcherFlightOccurrenceQueryInterface::class, $query);

        // Check-in opened yesterday and still open: carried over.
        $carriedOpen = $this->definition();
        $carriedOpenRun = $this->manualRun($carriedOpen, self::YESTERDAY, 1, 'open');

        // Check-in closed yesterday; a stray run exists today too: the live one wins.
        $carriedClosed = $this->definition();
        $carriedClosedRun = $this->manualRun($carriedClosed, self::YESTERDAY, 1, 'closed');
        $this->manualRun($carriedClosed, self::TODAY, 1, null);

        // Yesterday's run reached boarding: finished, not carried over.
        $finishedYesterday = $this->definition();
        $this->manualRun($finishedYesterday, self::YESTERDAY, 1, 'boarding');

        // Yesterday's run was never acted upon: not carried over.
        $scheduledYesterday = $this->definition();
        $this->manualRun($scheduledYesterday, self::YESTERDAY, 1, null);

        // Two days old and still open: abandoned, not carried over.
        $abandoned = $this->definition();
        $this->manualRun($abandoned, self::TWO_DAYS_AGO, 1, 'open');

        // Two runs today: the latest one is current.
        $twoRunsToday = $this->definition();
        $this->manualRun($twoRunsToday, self::TODAY, 1, 'boarding');
        $latestToday = $this->manualRun($twoRunsToday, self::TODAY, 2, null);

        $ours = [];
        foreach ($query->board(new DateTimeImmutable(self::TODAY)) as $row) {
            $ours[$row->flightDefinitionId][] = $row;
        }

        $this->assertCurrent($ours, $carriedOpen, $carriedOpenRun, self::YESTERDAY, 'check_in_open');
        $this->assertCurrent($ours, $carriedClosed, $carriedClosedRun, self::YESTERDAY, 'check_in_closed');
        $this->assertCurrent($ours, $twoRunsToday, $latestToday, self::TODAY, 'scheduled');
        self::assertArrayNotHasKey($finishedYesterday->getId()->toRfc4122(), $ours);
        self::assertArrayNotHasKey($scheduledYesterday->getId()->toRfc4122(), $ours);
        self::assertArrayNotHasKey($abandoned->getId()->toRfc4122(), $ours);
    }

    /** @param array<string, list<DispatcherFlightOccurrenceResult>> $board */
    private function assertCurrent(
        array $board,
        FlightDefinition $definition,
        FlightOccurrence $expected,
        string $operationalDate,
        string $status,
    ): void {
        $rows = $board[$definition->getId()->toRfc4122()] ?? [];
        self::assertCount(1, $rows);
        self::assertSame($expected->getId()->toRfc4122(), $rows[0]->id);
        self::assertSame($operationalDate, $rows[0]->operationalDate);
        self::assertSame($status, $rows[0]->status);
    }

    /** @param 'open'|'closed'|'boarding'|null $progress */
    private function manualRun(FlightDefinition $definition, string $date, int $sequenceNumber, ?string $progress): FlightOccurrence
    {
        $occurrence = FlightOccurrence::createManual($definition, new DateTimeImmutable($date), $sequenceNumber);
        $announcementId = Uuid::v7()->toRfc4122();
        if ($progress !== null) {
            $occurrence->openCheckIn($announcementId, [['id' => Uuid::v7()->toRfc4122(), 'code' => '1']]);
        }
        if ($progress === 'closed' || $progress === 'boarding') {
            $occurrence->closeCheckIn($announcementId);
        }
        if ($progress === 'boarding') {
            $occurrence->startBoarding($announcementId, ['id' => Uuid::v7()->toRfc4122(), 'code' => 'A5']);
        }
        $occurrence->pullEvents();
        $this->occurrences->save($occurrence);

        return $occurrence;
    }

    private function definition(): FlightDefinition
    {
        $definition = FlightDefinition::create(
            FlightNumber::fromString('BD'.random_int(1000, 9999)),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
        $definition->pullEvents();
        $this->definitions->save($definition);

        return $definition;
    }
}
