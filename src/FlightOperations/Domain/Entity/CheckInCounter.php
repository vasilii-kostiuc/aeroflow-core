<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Event\CheckInCounterActivated;
use App\FlightOperations\Domain\Event\CheckInCounterCreated;
use App\FlightOperations\Domain\Event\CheckInCounterDeactivated;
use App\FlightOperations\Domain\Event\CheckInCounterUpdated;
use App\FlightOperations\Domain\Exception\InvalidOperationalResourceException;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'check_in_counter')]
#[ORM\UniqueConstraint(name: 'UNIQ_CHECK_IN_COUNTER_CODE', columns: ['code'])]
final class CheckInCounter extends AggregateRoot
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
        $counter = new self();
        $counter->id = Uuid::v7();
        $counter->code = $code;
        $counter->displayName = '';
        $counter->sortOrder = 1;
        $counter->active = true;
        $counter->createdAt = $now;
        $counter->updatedAt = $now;
        $counter->updateDetails($code, $displayName, $sortOrder);
        $counter->pullEvents();
        $counter->recordEvent(new CheckInCounterCreated($counter->id->toRfc4122(), $code->toString(), $now));

        return $counter;
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
            $this->recordEvent(new CheckInCounterUpdated($this->id->toRfc4122(), $code->toString(), $this->updatedAt));
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
        $event = $active ? new CheckInCounterActivated($this->id->toRfc4122(), $this->updatedAt) : new CheckInCounterDeactivated($this->id->toRfc4122(), $this->updatedAt);
        $this->recordEvent($event);

        return true;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
