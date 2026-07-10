<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * The recorded fact that a playback integration event reached core.
 *
 * An integration artifact, not a domain aggregate: it protects idempotency of the
 * inbound queue (unique messageId) and gives the announcement a playback timeline
 * for observability. Announcement statuses are not derived from it in this slice.
 */
#[ORM\Entity]
#[ORM\Table(name: 'playback_event_receipt')]
#[ORM\UniqueConstraint(name: 'UNIQ_PLAYBACK_EVENT_RECEIPT_MESSAGE', columns: ['message_id'])]
#[ORM\Index(name: 'IDX_PLAYBACK_EVENT_RECEIPT_ANNOUNCEMENT', columns: ['announcement_id'])]
class PlaybackEventReceipt
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** messageId of the integration event; unique, so a redelivery cannot duplicate. */
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $messageId;

    #[ORM\Column(length: 64)]
    private string $event;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $announcementId;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $jobId;

    /** The moment playback reports the fact occurred (contract field, as sent). */
    #[ORM\Column(length: 64)]
    private string $occurredAt;

    #[ORM\Column]
    private DateTimeImmutable $receivedAt;

    private function __construct()
    {
    }

    public static function record(
        string $messageId,
        string $event,
        string $announcementId,
        string $jobId,
        string $occurredAt,
    ): self {
        $receipt = new self();
        $receipt->id = Uuid::v7();
        $receipt->messageId = Uuid::fromString($messageId);
        $receipt->event = $event;
        $receipt->announcementId = Uuid::fromString($announcementId);
        $receipt->jobId = Uuid::fromString($jobId);
        $receipt->occurredAt = $occurredAt;
        $receipt->receivedAt = new DateTimeImmutable();

        return $receipt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMessageId(): Uuid
    {
        return $this->messageId;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getAnnouncementId(): Uuid
    {
        return $this->announcementId;
    }

    public function getJobId(): Uuid
    {
        return $this->jobId;
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
