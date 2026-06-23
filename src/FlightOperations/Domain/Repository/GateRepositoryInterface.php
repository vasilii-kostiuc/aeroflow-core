<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Repository;

use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Symfony\Component\Uid\Uuid;

interface GateRepositoryInterface
{
    public function save(Gate $gate): void;

    public function findById(Uuid $id): ?Gate;

    public function findByCode(OperationalResourceCode $code): ?Gate;

    /** @return list<Gate> */
    public function findAll(?bool $active = null): array;
}
