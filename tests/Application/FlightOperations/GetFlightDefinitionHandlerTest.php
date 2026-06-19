<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\GetFlightDefinition\GetFlightDefinitionHandler;
use App\FlightOperations\Application\GetFlightDefinition\GetFlightDefinitionQuery;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetFlightDefinitionHandlerTest extends TestCase
{
    public function testReturnsExistingDefinition(): void
    {
        $repository = new InMemoryFlightDefinitionRepository();
        $definition = FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
        $repository->add($definition);
        $handler = new GetFlightDefinitionHandler($repository);

        $result = $handler(new GetFlightDefinitionQuery($definition->getId()->toRfc4122()));

        self::assertSame($definition->getId()->toRfc4122(), $result->id);
    }

    public function testThrowsWhenDefinitionDoesNotExist(): void
    {
        $handler = new GetFlightDefinitionHandler(new InMemoryFlightDefinitionRepository());

        $this->expectException(FlightDefinitionNotFoundException::class);

        $handler(new GetFlightDefinitionQuery(Uuid::v7()->toRfc4122()));
    }
}
