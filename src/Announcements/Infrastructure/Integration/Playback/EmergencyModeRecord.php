<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Core's mirror of playback's emergency mode state (task 026): a single row with a
 * fixed id, rewritten by every inbound report.
 *
 * An integration artifact, not a domain aggregate — the mode belongs to playback.
 * `occurredAt` is kept as the contract string playback sent and is also the ordering
 * key: a report older than the recorded one is a late redelivery and is ignored, so
 * a delayed `activated` can never resurrect a mode the dispatcher already lifted.
 */
#[ORM\Entity]
#[ORM\Table(name: 'emergency_mode_record')]
class EmergencyModeRecord
{
    /** The mode is global and singular, so the row has one fixed identity. */
    public const string SINGLETON_ID = '01990000-0000-7000-8000-00000000026e';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column]
    private bool $active;

    /** The moment playback reports the mode changed (contract field, as sent). */
    #[ORM\Column(length: 64)]
    private string $occurredAt;

    #[ORM\Column]
    private DateTimeImmutable $receivedAt;

    private function __construct()
    {
    }

    public static function initial(): self
    {
        $record = new self();
        $record->id = Uuid::fromString(self::SINGLETON_ID);
        $record->active = false;
        $record->occurredAt = '';
        $record->receivedAt = new DateTimeImmutable();

        return $record;
    }

    /**
     * Apply a report. Returns false when the report is not newer than the recorded
     * one (redelivery or out-of-order arrival), leaving the state untouched.
     */
    public function apply(bool $active, string $occurredAt): bool
    {
        if ($occurredAt <= $this->occurredAt) {
            return false;
        }

        $this->active = $active;
        $this->occurredAt = $occurredAt;
        $this->receivedAt = new DateTimeImmutable();

        return true;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getOccurredAt(): string
    {
        return $this->occurredAt;
    }

    public function getReceivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }
}
