<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Exception\OperationalResourceNotFoundException;
use App\FlightOperations\Domain\Repository\GateRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Uid\Uuid;

final readonly class GateDirectory
{
    public function __construct(
        private GateRepositoryInterface $gates,
        private DomainEventPublisher $events,
    ) {
    }

    public function create(string $code, string $name, int $sortOrder): OperationalResourceResult
    {
        $value = OperationalResourceCode::fromString($code);
        if ($this->gates->findByCode($value) !== null) {
            throw DuplicateOperationalResourceException::forTypeAndCode('Gate', $value->toString());
        }

        $gate = Gate::create($value, $name, $sortOrder);
        $this->gates->save($gate);
        $this->dispatch($gate);

        return OperationalResourceResult::fromGate($gate);
    }

    public function update(string $id, string $code, string $name, int $sortOrder): OperationalResourceResult
    {
        $gate = $this->find($id);
        $gate->updateDetails(OperationalResourceCode::fromString($code), $name, $sortOrder);
        $this->gates->save($gate);
        $this->dispatch($gate);

        return OperationalResourceResult::fromGate($gate);
    }

    public function changeStatus(string $id, bool $active): OperationalResourceResult
    {
        $gate = $this->find($id);
        $active ? $gate->activate() : $gate->deactivate();
        $this->gates->save($gate);
        $this->dispatch($gate);

        return OperationalResourceResult::fromGate($gate);
    }

    /** @return list<OperationalResourceResult> */
    public function list(?bool $active): array
    {
        return array_map(
            OperationalResourceResult::fromGate(...),
            $this->gates->findAll($active),
        );
    }

    private function find(string $id): Gate
    {
        if (!Uuid::isValid($id)) {
            throw OperationalResourceNotFoundException::withId('Gate', $id);
        }

        return $this->gates->findById(Uuid::fromString($id))
            ?? throw OperationalResourceNotFoundException::withId('Gate', $id);
    }

    private function dispatch(Gate $gate): void
    {
        $this->events->publish(...$gate->pullEvents());
    }
}
