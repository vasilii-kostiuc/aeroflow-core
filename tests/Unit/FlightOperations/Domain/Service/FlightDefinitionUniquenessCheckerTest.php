<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\Service;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Service\FlightDefinitionUniquenessChecker;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use PHPUnit\Framework\TestCase;

final class FlightDefinitionUniquenessCheckerTest extends TestCase
{
    public function testRejectsExistingBusinessKey(): void
    {
        $repository = new InMemoryFlightDefinitionRepository();
        $repository->add(FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        ));
        $checker = new FlightDefinitionUniquenessChecker($repository);

        $this->expectException(DuplicateFlightDefinitionException::class);

        $checker->ensureUnique(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
    }

    public function testAllowsCurrentDefinitionWhenUpdating(): void
    {
        $repository = new InMemoryFlightDefinitionRepository();
        $definition = FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
        $repository->add($definition);
        $checker = new FlightDefinitionUniquenessChecker($repository);

        $checker->ensureUnique(
            $definition->getFlightNumber(),
            $definition->getDirection(),
            $definition->getOriginAirportCode(),
            $definition->getDestinationAirportCode(),
            $definition->getId(),
        );

        self::addToAssertionCount(1);
    }
}
