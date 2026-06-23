<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Exception\OperationalResourceNotFoundException;
use App\FlightOperations\Domain\Repository\CheckInCounterRepositoryInterface;
use App\FlightOperations\Domain\Repository\GateRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class OperationalResourceDirectory
{
    public function __construct(
        private CheckInCounterRepositoryInterface $counters,
        private GateRepositoryInterface $gates,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    /** @return array<string,mixed> */
    public function create(string $type, string $code, string $name, int $sortOrder): array
    {
        $value = OperationalResourceCode::fromString($code);
        $repository = $type === 'check-in counter' ? $this->counters : $this->gates;
        if ($repository->findByCode($value) !== null) {
            throw DuplicateOperationalResourceException::forTypeAndCode($type, $value->toString());
        }
        $resource = $type === 'check-in counter'
            ? CheckInCounter::create($value, $name, $sortOrder)
            : Gate::create($value, $name, $sortOrder);
        $repository->save($resource);
        $this->dispatch($resource);

        return $this->result($resource);
    }

    /** @return array<string,mixed> */
    public function update(string $type, string $id, string $code, string $name, int $sortOrder): array
    {
        $resource = $this->find($type, $id);
        $resource->updateDetails(OperationalResourceCode::fromString($code), $name, $sortOrder);
        ($type === 'check-in counter' ? $this->counters : $this->gates)->save($resource);
        $this->dispatch($resource);

        return $this->result($resource);
    }

    /** @return array<string,mixed> */
    public function status(string $type, string $id, bool $active): array
    {
        $resource = $this->find($type, $id);
        $active ? $resource->activate() : $resource->deactivate();
        ($type === 'check-in counter' ? $this->counters : $this->gates)->save($resource);
        $this->dispatch($resource);

        return $this->result($resource);
    }

    /** @return list<array<string,mixed>> */
    public function list(string $type, ?bool $active): array
    {
        $resources = ($type === 'check-in counter' ? $this->counters : $this->gates)->findAll($active);

        return array_map($this->result(...), $resources);
    }

    private function find(string $type, string $id): CheckInCounter|Gate
    {
        if (!Uuid::isValid($id)) {
            throw OperationalResourceNotFoundException::withId($type, $id);
        }

        return ($type === 'check-in counter' ? $this->counters : $this->gates)->findById(Uuid::fromString($id))
            ?? throw OperationalResourceNotFoundException::withId($type, $id);
    }

    /** @return array<string,mixed> */
    private function result(CheckInCounter|Gate $resource): array
    {
        return [
            'id' => $resource->getId()->toRfc4122(),
            'code' => $resource->getCode()->toString(),
            'displayName' => $resource->getDisplayName(),
            'sortOrder' => $resource->getSortOrder(),
            'active' => $resource->isActive(),
            'createdAt' => $resource->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $resource->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function dispatch(CheckInCounter|Gate $resource): void
    {
        foreach ($resource->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
