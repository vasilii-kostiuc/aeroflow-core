<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightDefinitionActivated;
use App\FlightOperations\Domain\Event\FlightDefinitionCreated;
use App\FlightOperations\Domain\Event\FlightDefinitionDeactivated;
use App\FlightOperations\Domain\Event\FlightDefinitionUpdated;
use App\FlightOperations\Domain\Exception\InvalidFlightRouteException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'flight_definition')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_FLIGHT_DEFINITION_BUSINESS_KEY',
    columns: ['flight_number', 'direction', 'origin_airport_code', 'destination_airport_code'],
)]
class FlightDefinition extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Embedded(class: FlightNumber::class, columnPrefix: false)]
    private FlightNumber $flightNumber;

    #[ORM\Column(length: 16, enumType: FlightDirection::class)]
    private FlightDirection $direction;

    #[ORM\Embedded(class: AirportCode::class, columnPrefix: 'origin_airport_')]
    private AirportCode $originAirportCode;

    #[ORM\Embedded(class: AirportCode::class, columnPrefix: 'destination_airport_')]
    private AirportCode $destinationAirportCode;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
    ): self {
        self::assertRoute($originAirportCode, $destinationAirportCode);

        $now = self::now();
        $flightDefinition = new self();
        $flightDefinition->id = Uuid::v7();
        $flightDefinition->flightNumber = $flightNumber;
        $flightDefinition->direction = $direction;
        $flightDefinition->originAirportCode = $originAirportCode;
        $flightDefinition->destinationAirportCode = $destinationAirportCode;
        $flightDefinition->active = true;
        $flightDefinition->createdAt = $now;
        $flightDefinition->updatedAt = $now;

        $flightDefinition->recordEvent(new FlightDefinitionCreated(
            $flightDefinition->id->toRfc4122(),
            $flightNumber->toString(),
            $direction->value,
            $originAirportCode->toString(),
            $destinationAirportCode->toString(),
            $now,
        ));

        return $flightDefinition;
    }

    public function updateDetails(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
    ): bool {
        self::assertRoute($originAirportCode, $destinationAirportCode);

        if (
            $this->flightNumber->equals($flightNumber)
            && $this->direction === $direction
            && $this->originAirportCode->equals($originAirportCode)
            && $this->destinationAirportCode->equals($destinationAirportCode)
        ) {
            return false;
        }

        $this->flightNumber = $flightNumber;
        $this->direction = $direction;
        $this->originAirportCode = $originAirportCode;
        $this->destinationAirportCode = $destinationAirportCode;
        $this->updatedAt = self::now();

        $this->recordEvent(new FlightDefinitionUpdated(
            $this->id->toRfc4122(),
            $flightNumber->toString(),
            $direction->value,
            $originAirportCode->toString(),
            $destinationAirportCode->toString(),
            $this->updatedAt,
        ));

        return true;
    }

    public function activate(): bool
    {
        if ($this->active) {
            return false;
        }

        $this->active = true;
        $this->updatedAt = self::now();
        $this->recordEvent(new FlightDefinitionActivated($this->id->toRfc4122(), $this->updatedAt));

        return true;
    }

    public function deactivate(): bool
    {
        if (!$this->active) {
            return false;
        }

        $this->active = false;
        $this->updatedAt = self::now();
        $this->recordEvent(new FlightDefinitionDeactivated($this->id->toRfc4122(), $this->updatedAt));

        return true;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFlightNumber(): FlightNumber
    {
        return $this->flightNumber;
    }

    public function getDirection(): FlightDirection
    {
        return $this->direction;
    }

    public function getOriginAirportCode(): AirportCode
    {
        return $this->originAirportCode;
    }

    public function getDestinationAirportCode(): AirportCode
    {
        return $this->destinationAirportCode;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function assertRoute(AirportCode $origin, AirportCode $destination): void
    {
        if ($origin->equals($destination)) {
            throw InvalidFlightRouteException::sameAirports($origin->toString());
        }
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
