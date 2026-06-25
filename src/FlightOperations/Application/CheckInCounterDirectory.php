<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Exception\OperationalResourceNotFoundException;
use App\FlightOperations\Domain\Repository\CheckInCounterRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Uid\Uuid;

final readonly class CheckInCounterDirectory
{
    public function __construct(
        private CheckInCounterRepositoryInterface $counters,
        private DomainEventPublisher $events,
    ) {
    }

    public function create(string $code, string $name, int $sortOrder): OperationalResourceResult
    {
        $value = OperationalResourceCode::fromString($code);
        if ($this->counters->findByCode($value) !== null) {
            throw DuplicateOperationalResourceException::forTypeAndCode('Check-in counter', $value->toString());
        }

        $counter = CheckInCounter::create($value, $name, $sortOrder);
        $this->counters->save($counter);
        $this->dispatch($counter);

        return OperationalResourceResult::fromCheckInCounter($counter);
    }

    public function update(string $id, string $code, string $name, int $sortOrder): OperationalResourceResult
    {
        $counter = $this->find($id);
        $counter->updateDetails(OperationalResourceCode::fromString($code), $name, $sortOrder);
        $this->counters->save($counter);
        $this->dispatch($counter);

        return OperationalResourceResult::fromCheckInCounter($counter);
    }

    public function changeStatus(string $id, bool $active): OperationalResourceResult
    {
        $counter = $this->find($id);
        $active ? $counter->activate() : $counter->deactivate();
        $this->counters->save($counter);
        $this->dispatch($counter);

        return OperationalResourceResult::fromCheckInCounter($counter);
    }

    /** @return list<OperationalResourceResult> */
    public function list(?bool $active): array
    {
        return array_map(
            OperationalResourceResult::fromCheckInCounter(...),
            $this->counters->findAll($active),
        );
    }

    private function find(string $id): CheckInCounter
    {
        if (!Uuid::isValid($id)) {
            throw OperationalResourceNotFoundException::withId('Check-in counter', $id);
        }

        return $this->counters->findById(Uuid::fromString($id))
            ?? throw OperationalResourceNotFoundException::withId('Check-in counter', $id);
    }

    private function dispatch(CheckInCounter $counter): void
    {
        $this->events->publish(...$counter->pullEvents());
    }
}
