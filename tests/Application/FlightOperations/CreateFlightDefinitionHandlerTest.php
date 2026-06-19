<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\CreateFlightDefinition\CreateFlightDefinitionCommand;
use App\FlightOperations\Application\CreateFlightDefinition\CreateFlightDefinitionHandler;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightDefinitionCreated;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Service\FlightDefinitionUniquenessChecker;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Application\UserAccess\Support\RecordingMessageBus;
use PHPUnit\Framework\TestCase;

final class CreateFlightDefinitionHandlerTest extends TestCase
{
    public function testCreatesSavesAndPublishesEvent(): void
    {
        $repository = new InMemoryFlightDefinitionRepository();
        $eventBus = new RecordingMessageBus();
        $handler = new CreateFlightDefinitionHandler(
            $repository,
            new FlightDefinitionUniquenessChecker($repository),
            $eventBus,
        );

        $result = $handler(new CreateFlightDefinitionCommand('5f123', 'departure', 'kiv', 'fco'));

        self::assertSame('5F123', $result->flightNumber);
        self::assertSame('KIV', $result->originAirportCode);
        self::assertSame(1, $repository->saveCalls);
        self::assertCount(1, $eventBus->messages);
        self::assertInstanceOf(FlightDefinitionCreated::class, $eventBus->messages[0]);
    }

    public function testRejectsDuplicateBusinessKey(): void
    {
        $repository = new InMemoryFlightDefinitionRepository();
        $repository->add(FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        ));
        $handler = new CreateFlightDefinitionHandler(
            $repository,
            new FlightDefinitionUniquenessChecker($repository),
            new RecordingMessageBus(),
        );

        $this->expectException(DuplicateFlightDefinitionException::class);

        $handler(new CreateFlightDefinitionCommand('5F123', 'departure', 'KIV', 'FCO'));
    }
}
