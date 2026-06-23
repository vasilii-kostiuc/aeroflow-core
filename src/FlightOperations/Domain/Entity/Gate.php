<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Event\GateActivated;
use App\FlightOperations\Domain\Event\GateCreated;
use App\FlightOperations\Domain\Event\GateDeactivated;
use App\FlightOperations\Domain\Event\GateUpdated;
use App\FlightOperations\Domain\Exception\InvalidOperationalResourceException;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gate')]
#[ORM\UniqueConstraint(name: 'UNIQ_GATE_CODE', columns: ['code'])]
final class Gate extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Embedded(class: OperationalResourceCode::class, columnPrefix: false)]
    private OperationalResourceCode $code;

    #[ORM\Column(length: 128)]
    private string $displayName;

    #[ORM\Column]
    private int $sortOrder;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(OperationalResourceCode $code, string $displayName, int $sortOrder): self
    {
        $now = self::now();
        $gate = new self();
        $gate->id = Uuid::v7();
        $gate->code = $code;
        $gate->displayName = '';
        $gate->sortOrder = 1;
        $gate->active = true;
        $gate->createdAt = $now;
        $gate->updatedAt = $now;
        $gate->updateDetails($code, $displayName, $sortOrder);
        $gate->pullEvents();
        $gate->recordEvent(new GateCreated($gate->id->toRfc4122(), $code->toString(), $now));

        return $gate;
    }

    public function updateDetails(OperationalResourceCode $code, string $displayName, int $sortOrder): bool
    {
        $displayName = trim($displayName);
        if ($displayName === '' || mb_strlen($displayName) > 128) {
            throw InvalidOperationalResourceException::emptyName();
        }
        if ($sortOrder < 1) {
            throw InvalidOperationalResourceException::invalidSortOrder();
        }
        if ($this->code->equals($code) && $this->displayName === $displayName && $this->sortOrder === $sortOrder) {
            return false;
        }
        $this->code = $code;
        $this->displayName = $displayName;
        $this->sortOrder = $sortOrder;
        $this->updatedAt = self::now();
        if (isset($this->id)) {
            $this->recordEvent(new GateUpdated($this->id->toRfc4122(), $code->toString(), $this->updatedAt));
        }

        return true;
    }

    public function activate(): bool
    {
        return $this->changeStatus(true);
    }

    public function deactivate(): bool
    {
        return $this->changeStatus(false);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): OperationalResourceCode
    {
        return $this->code;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
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

    private function changeStatus(bool $active): bool
    {
        if ($this->active === $active) {
            return false;
        }
        $this->active = $active;
        $this->updatedAt = self::now();
        $event = $active ? new GateActivated($this->id->toRfc4122(), $this->updatedAt) : new GateDeactivated($this->id->toRfc4122(), $this->updatedAt);
        $this->recordEvent($event);

        return true;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
