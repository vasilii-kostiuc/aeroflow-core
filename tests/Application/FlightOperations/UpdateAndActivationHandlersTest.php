<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\ActivateFlightDefinition\ActivateFlightDefinitionCommand;
use App\FlightOperations\Application\ActivateFlightDefinition\ActivateFlightDefinitionHandler;
use App\FlightOperations\Application\DeactivateFlightDefinition\DeactivateFlightDefinitionCommand;
use App\FlightOperations\Application\DeactivateFlightDefinition\DeactivateFlightDefinitionHandler;
use App\FlightOperations\Application\UpdateFlightDefinition\UpdateFlightDefinitionCommand;
use App\FlightOperations\Application\UpdateFlightDefinition\UpdateFlightDefinitionHandler;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Service\FlightDefinitionUniquenessChecker;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;

final class UpdateAndActivationHandlersTest extends TestCase
{
    public function testNoOpUpdateDoesNotSaveOrPublish(): void
    {
        [$definition, $repository] = $this->definitionAndRepository();
        $eventBus = new RecordingEventPublisher();
        $handler = new UpdateFlightDefinitionHandler(
            $repository,
            new FlightDefinitionUniquenessChecker($repository),
            $eventBus,
        );

        $result = $handler(new UpdateFlightDefinitionCommand(
            $definition->getId()->toRfc4122(),
            '5F123',
            'departure',
            'KIV',
            'FCO',
        ));

        self::assertSame('5F123', $result->flightNumber);
        self::assertSame(0, $repository->saveCalls);
        self::assertSame([], $eventBus->messages);
    }

    public function testDeactivateAndActivateAreIdempotent(): void
    {
        [$definition, $repository] = $this->definitionAndRepository();
        $eventBus = new RecordingEventPublisher();
        $deactivate = new DeactivateFlightDefinitionHandler($repository, $eventBus);
        $activate = new ActivateFlightDefinitionHandler($repository, $eventBus);
        $id = $definition->getId()->toRfc4122();

        self::assertFalse($deactivate(new DeactivateFlightDefinitionCommand($id))->active);
        self::assertFalse($deactivate(new DeactivateFlightDefinitionCommand($id))->active);
        self::assertTrue($activate(new ActivateFlightDefinitionCommand($id))->active);
        self::assertTrue($activate(new ActivateFlightDefinitionCommand($id))->active);

        self::assertSame(2, $repository->saveCalls);
        self::assertCount(2, $eventBus->messages);
    }

    public function testUpdateRejectsDuplicateBusinessKey(): void
    {
        [$definition, $repository] = $this->definitionAndRepository();
        $duplicate = FlightDefinition::create(
            FlightNumber::fromString('WZZ42'),
            FlightDirection::Arrival,
            AirportCode::fromString('FCO'),
            AirportCode::fromString('KIV'),
        );
        $repository->add($duplicate);
        $handler = new UpdateFlightDefinitionHandler(
            $repository,
            new FlightDefinitionUniquenessChecker($repository),
            new RecordingEventPublisher(),
        );

        $this->expectException(DuplicateFlightDefinitionException::class);

        $handler(new UpdateFlightDefinitionCommand(
            $definition->getId()->toRfc4122(),
            'WZZ42',
            'arrival',
            'FCO',
            'KIV',
        ));
    }

    /**
     * @return array{FlightDefinition, InMemoryFlightDefinitionRepository}
     */
    private function definitionAndRepository(): array
    {
        $definition = FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
        $definition->pullEvents();
        $repository = new InMemoryFlightDefinitionRepository();
        $repository->add($definition);

        return [$definition, $repository];
    }
}
