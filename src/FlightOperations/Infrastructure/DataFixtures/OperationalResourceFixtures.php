<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\DataFixtures;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\Repository\CheckInCounterRepositoryInterface;
use App\FlightOperations\Domain\Repository\GateRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class OperationalResourceFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @var list<array{code: string, displayName: string, sortOrder: int}>
     */
    private const CHECK_IN_COUNTERS = [
        ['code' => '1', 'displayName' => 'Стойка регистрации 1', 'sortOrder' => 1],
        ['code' => '2', 'displayName' => 'Стойка регистрации 2', 'sortOrder' => 2],
        ['code' => '3', 'displayName' => 'Стойка регистрации 3', 'sortOrder' => 3],
        ['code' => '4', 'displayName' => 'Стойка регистрации 4', 'sortOrder' => 4],
        ['code' => '5', 'displayName' => 'Стойка регистрации 5', 'sortOrder' => 5],
        ['code' => '6', 'displayName' => 'Стойка регистрации 6', 'sortOrder' => 6],
        ['code' => '7', 'displayName' => 'Стойка регистрации 7', 'sortOrder' => 7],
        ['code' => '8', 'displayName' => 'Стойка регистрации 8', 'sortOrder' => 8],
        ['code' => '9', 'displayName' => 'Стойка регистрации 9', 'sortOrder' => 9],
        ['code' => '10', 'displayName' => 'Стойка регистрации 10', 'sortOrder' => 10],
        ['code' => '11', 'displayName' => 'Стойка регистрации 11', 'sortOrder' => 11],
        ['code' => '12', 'displayName' => 'Стойка регистрации 12', 'sortOrder' => 12],
    ];

    /**
     * @var list<array{code: string, displayName: string, sortOrder: int}>
     */
    private const GATES = [
        ['code' => 'A1', 'displayName' => 'Выход A1', 'sortOrder' => 1],
        ['code' => 'A2', 'displayName' => 'Выход A2', 'sortOrder' => 2],
        ['code' => 'A3', 'displayName' => 'Выход A3', 'sortOrder' => 3],
        ['code' => 'A4', 'displayName' => 'Выход A4', 'sortOrder' => 4],
    ];

    public function __construct(
        private readonly CheckInCounterRepositoryInterface $counters,
        private readonly GateRepositoryInterface $gates,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::CHECK_IN_COUNTERS as $data) {
            $code = OperationalResourceCode::fromString($data['code']);

            if ($this->counters->findByCode($code) !== null) {
                continue;
            }

            $counter = CheckInCounter::create($code, $data['displayName'], $data['sortOrder']);
            $counter->pullEvents();

            $manager->persist($counter);
        }

        foreach (self::GATES as $data) {
            $code = OperationalResourceCode::fromString($data['code']);

            if ($this->gates->findByCode($code) !== null) {
                continue;
            }

            $gate = Gate::create($code, $data['displayName'], $data['sortOrder']);
            $gate->pullEvents();

            $manager->persist($gate);
        }

        $manager->flush();
    }

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['flight-operations', 'operational-resources'];
    }
}
