<?php

declare(strict_types=1);

namespace App\Tests\Integration\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FlightDefinitionRepositoryTest extends KernelTestCase
{
    public function testSavesFindsAndChecksBusinessKey(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(FlightDefinitionRepositoryInterface::class);
        self::assertInstanceOf(FlightDefinitionRepositoryInterface::class, $repository);
        $definition = $this->definition('5F'.random_int(1000, 9999));
        $definition->pullEvents();

        $repository->save($definition);

        self::assertSame($definition, $repository->findById($definition->getId()));
        self::assertTrue($repository->hasConflictingDefinition(
            $definition->getFlightNumber(),
            $definition->getDirection(),
            $definition->getOriginAirportCode(),
            $definition->getDestinationAirportCode(),
        ));
        self::assertFalse($repository->hasConflictingDefinition(
            $definition->getFlightNumber(),
            $definition->getDirection(),
            $definition->getOriginAirportCode(),
            $definition->getDestinationAirportCode(),
            $definition->getId(),
        ));
    }

    public function testUniqueIndexIsConvertedToDomainException(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(FlightDefinitionRepositoryInterface::class);
        self::assertInstanceOf(FlightDefinitionRepositoryInterface::class, $repository);
        $flightNumber = '5F'.random_int(1000, 9999);
        $repository->save($this->definition($flightNumber));

        $this->expectException(DuplicateFlightDefinitionException::class);

        $repository->save($this->definition($flightNumber));
    }

    private function definition(string $flightNumber): FlightDefinition
    {
        return FlightDefinition::create(
            FlightNumber::fromString($flightNumber),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
    }
}
