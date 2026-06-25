<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Exception\InvalidAnnouncementResourcesException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'announcement')]
#[ORM\Index(name: 'IDX_ANNOUNCEMENT_CREATED_AT', columns: ['created_at'])]
final class Announcement extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;
    #[ORM\Column(length: 32, enumType: AnnouncementType::class)]
    private AnnouncementType $type;
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $flightDefinitionId;
    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $flightOccurrenceId;
    /** @var list<array{id:string,code:string}> */
    #[ORM\Column(type: 'json')]
    private array $checkInCounters;
    /** @var array{id:string,code:string}|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $gate;
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $languageCodes;
    /** @var list<array{languageCode:string,sortOrder:int,items:list<array<string,mixed>>}> */
    #[ORM\Column(type: 'json')]
    private array $audioSequence;
    #[ORM\Column(length: 32, enumType: AnnouncementStatus::class)]
    private AnnouncementStatus $status;
    #[ORM\Column]
    private DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $cancelledAt;

    private function __construct()
    {
    }

    /**
     * @param list<array{id:string,code:string}>                                             $checkInCounters
     * @param array{id:string,code:string}|null                                              $gate
     * @param list<array{languageCode:string,sortOrder:int,items:list<array<string,mixed>>}> $audioSequence
     */
    public static function createPrepared(
        AnnouncementType $type,
        string $flightDefinitionId,
        AnnouncementLanguages $languages,
        array $checkInCounters,
        ?array $gate,
        array $audioSequence,
        ?string $flightOccurrenceId = null,
    ): self {
        if (!Uuid::isValid($flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($flightDefinitionId);
        }
        if ($flightOccurrenceId !== null && !Uuid::isValid($flightOccurrenceId)) {
            throw InvalidFlightDefinitionIdException::forValue($flightOccurrenceId);
        }
        if ($type->requiresCheckInCounters() && [] === $checkInCounters) {
            throw InvalidAnnouncementResourcesException::missingCheckInCounters();
        }
        if ($type->requiresGate() && null === $gate) {
            throw InvalidAnnouncementResourcesException::missingGate();
        }

        $now = self::now();
        $announcement = new self();
        $announcement->id = Uuid::v7();
        $announcement->type = $type;
        $announcement->flightDefinitionId = Uuid::fromString($flightDefinitionId);
        $announcement->flightOccurrenceId = $flightOccurrenceId === null ? null : Uuid::fromString($flightOccurrenceId);
        $announcement->checkInCounters = $checkInCounters;
        $announcement->gate = $gate;
        $announcement->languageCodes = $languages->toStrings();
        $announcement->audioSequence = $audioSequence;
        $announcement->status = AnnouncementStatus::Prepared;
        $announcement->createdAt = $now;
        $announcement->cancelledAt = null;
        $announcement->recordEvent(new AnnouncementCreated(
            $announcement->id->toRfc4122(),
            $type->value,
            $flightDefinitionId,
            $languages->toStrings(),
            $now,
        ));

        return $announcement;
    }

    public function cancel(): bool
    {
        if ($this->status === AnnouncementStatus::Cancelled) {
            return false;
        }
        $this->status = AnnouncementStatus::Cancelled;
        $this->cancelledAt = self::now();
        $this->recordEvent(new AnnouncementCancelled($this->id->toRfc4122(), $this->cancelledAt));

        return true;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): AnnouncementType
    {
        return $this->type;
    }

    public function getFlightDefinitionId(): Uuid
    {
        return $this->flightDefinitionId;
    }

    public function getFlightOccurrenceId(): ?Uuid
    {
        return $this->flightOccurrenceId;
    }

    /** @return list<array{id:string,code:string}> */
    public function getCheckInCounters(): array
    {
        return $this->checkInCounters;
    }

    /** @return array{id:string,code:string}|null */
    public function getGate(): ?array
    {
        return $this->gate;
    }

    /** @return list<array{languageCode:string,sortOrder:int,items:list<array<string,mixed>>}> */
    public function getAudioSequence(): array
    {
        return $this->audioSequence;
    }

    public function getLanguages(): AnnouncementLanguages
    {
        return AnnouncementLanguages::fromCodes(...array_map(static fn (string $code): LanguageCode => LanguageCode::fromString($code), $this->languageCodes));
    }

    public function getStatus(): AnnouncementStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
