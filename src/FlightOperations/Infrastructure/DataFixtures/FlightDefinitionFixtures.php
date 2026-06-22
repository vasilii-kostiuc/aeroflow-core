<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\DataFixtures;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class FlightDefinitionFixtures extends Fixture implements FixtureGroupInterface
{
    private const CHISINAU_AIRPORT_CODE = 'RMO';

    /**
     * Representative passenger flights for Chișinău International Airport.
     *
     * These fixtures are development data, not an operational timetable.
     *
     * @var list<array{
     *     flightNumber: string,
     *     direction: FlightDirection,
     *     origin: string,
     *     destination: string
     * }>
     */
    private const FLIGHTS = [
        [
            'flightNumber' => '5F325',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'IST',
        ],
        [
            'flightNumber' => '5F326',
            'direction' => FlightDirection::Arrival,
            'origin' => 'IST',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'TK270',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'IST',
        ],
        [
            'flightNumber' => 'TK269',
            'direction' => FlightDirection::Arrival,
            'origin' => 'IST',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'RO202',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'OTP',
        ],
        [
            'flightNumber' => 'RO201',
            'direction' => FlightDirection::Arrival,
            'origin' => 'OTP',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'LO514',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'WAW',
        ],
        [
            'flightNumber' => 'LO513',
            'direction' => FlightDirection::Arrival,
            'origin' => 'WAW',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'OS656',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'VIE',
        ],
        [
            'flightNumber' => 'OS655',
            'direction' => FlightDirection::Arrival,
            'origin' => 'VIE',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => '5F617',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'LTN',
        ],
        [
            'flightNumber' => '5F618',
            'direction' => FlightDirection::Arrival,
            'origin' => 'LTN',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'H4481',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'CDG',
        ],
        [
            'flightNumber' => 'H4482',
            'direction' => FlightDirection::Arrival,
            'origin' => 'CDG',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'W43935',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'FCO',
        ],
        [
            'flightNumber' => 'W43936',
            'direction' => FlightDirection::Arrival,
            'origin' => 'FCO',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => '5F151',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'TLV',
        ],
        [
            'flightNumber' => '5F152',
            'direction' => FlightDirection::Arrival,
            'origin' => 'TLV',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'H4401',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'FRA',
        ],
        [
            'flightNumber' => 'H4402',
            'direction' => FlightDirection::Arrival,
            'origin' => 'FRA',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => '5F215',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'DUB',
        ],
        [
            'flightNumber' => '5F216',
            'direction' => FlightDirection::Arrival,
            'origin' => 'DUB',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
        [
            'flightNumber' => 'H4493',
            'direction' => FlightDirection::Departure,
            'origin' => self::CHISINAU_AIRPORT_CODE,
            'destination' => 'BCN',
        ],
        [
            'flightNumber' => 'H4494',
            'direction' => FlightDirection::Arrival,
            'origin' => 'BCN',
            'destination' => self::CHISINAU_AIRPORT_CODE,
        ],
    ];

    public function __construct(
        private readonly FlightDefinitionRepositoryInterface $repository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::FLIGHTS as $flight) {
            $flightNumber = FlightNumber::fromString($flight['flightNumber']);
            $origin = AirportCode::fromString($flight['origin']);
            $destination = AirportCode::fromString($flight['destination']);

            if ($this->repository->hasConflictingDefinition(
                $flightNumber,
                $flight['direction'],
                $origin,
                $destination,
            )) {
                continue;
            }

            $definition = FlightDefinition::create(
                $flightNumber,
                $flight['direction'],
                $origin,
                $destination,
            );
            $definition->pullEvents();

            $manager->persist($definition);
        }

        $manager->flush();
    }

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['flight-operations'];
    }
}
