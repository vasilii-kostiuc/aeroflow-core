<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Repository;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Symfony\Component\Uid\Uuid;

interface CheckInCounterRepositoryInterface
{
    public function save(CheckInCounter $counter): void;

    public function findById(Uuid $id): ?CheckInCounter;

    public function findByCode(OperationalResourceCode $code): ?CheckInCounter;

    /** @return list<CheckInCounter> */
    public function findAll(?bool $active = null): array;
}
