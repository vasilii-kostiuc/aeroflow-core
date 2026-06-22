<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\DataFixtures;

use App\FlightOperations\Domain\Entity\Airport;
use App\FlightOperations\Domain\Repository\AirportRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class AirportFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @var list<array{code: string, name: string, city: string, country: string}>
     */
    private const AIRPORTS = [
        ['code' => 'RMO', 'name' => 'Aeroportul Internațional Chișinău', 'city' => 'Кишинёв', 'country' => 'MD'],
        ['code' => 'IST', 'name' => 'Istanbul Airport', 'city' => 'Стамбул', 'country' => 'TR'],
        ['code' => 'OTP', 'name' => 'Henri Coandă International Airport', 'city' => 'Бухарест', 'country' => 'RO'],
        ['code' => 'WAW', 'name' => 'Warsaw Chopin Airport', 'city' => 'Варшава', 'country' => 'PL'],
        ['code' => 'VIE', 'name' => 'Vienna International Airport', 'city' => 'Вена', 'country' => 'AT'],
        ['code' => 'LTN', 'name' => 'London Luton Airport', 'city' => 'Лондон', 'country' => 'GB'],
        ['code' => 'CDG', 'name' => 'Charles de Gaulle Airport', 'city' => 'Париж', 'country' => 'FR'],
        ['code' => 'FCO', 'name' => 'Leonardo da Vinci–Fiumicino Airport', 'city' => 'Рим', 'country' => 'IT'],
        ['code' => 'TLV', 'name' => 'Ben Gurion Airport', 'city' => 'Тель-Авив', 'country' => 'IL'],
        ['code' => 'FRA', 'name' => 'Frankfurt Airport', 'city' => 'Франкфурт', 'country' => 'DE'],
        ['code' => 'DUB', 'name' => 'Dublin Airport', 'city' => 'Дублин', 'country' => 'IE'],
        ['code' => 'BCN', 'name' => 'Josep Tarradellas Barcelona–El Prat Airport', 'city' => 'Барселона', 'country' => 'ES'],
    ];

    public function __construct(private readonly AirportRepositoryInterface $repository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::AIRPORTS as $data) {
            $code = AirportCode::fromString($data['code']);

            if ($this->repository->findByCode($code) !== null) {
                continue;
            }

            $manager->persist(Airport::create($code, $data['name'], $data['city'], $data['country']));
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['airport-directory', 'flight-operations'];
    }
}
