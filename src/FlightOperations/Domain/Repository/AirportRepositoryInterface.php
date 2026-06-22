<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Repository;

use App\FlightOperations\Domain\Entity\Airport;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use Symfony\Component\Uid\Uuid;

interface AirportRepositoryInterface
{
    public function save(Airport $airport): void;

    public function findById(Uuid $id): ?Airport;

    public function findByCode(AirportCode $code): ?Airport;
}
