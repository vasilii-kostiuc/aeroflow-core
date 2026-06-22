<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Event\AnnouncementLanguagesChanged;
use App\Announcements\Domain\Exception\AnnouncementLanguagesCannotBeChangedException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Announcements\Domain\ValueObject\CheckInCounterRange;
use App\Announcements\Domain\ValueObject\GateCode;
use App\Announcements\Domain\ValueObject\LanguageCode;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'announcement')]
final class Announcement extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 32, enumType: AnnouncementType::class)]
    private AnnouncementType $type;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $flightDefinitionId;

    #[ORM\Column(nullable: true)]
    private ?int $checkInCounterStart;

    #[ORM\Column(nullable: true)]
    private ?int $checkInCounterEnd;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $gateCode;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $languageCodes;

    #[ORM\Column(length: 32, enumType: AnnouncementStatus::class)]
    private AnnouncementStatus $status;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $cancelledAt;

    private function __construct()
    {
    }

    public static function openCheckIn(
        string $flightDefinitionId,
        CheckInCounterRange $counterRange,
        AnnouncementLanguages $languages,
    ): self {
        return self::create(
            AnnouncementType::CheckInOpening,
            $flightDefinitionId,
            $languages,
            $counterRange,
        );
    }

    public static function closeCheckIn(
        string $flightDefinitionId,
        CheckInCounterRange $counterRange,
        AnnouncementLanguages $languages,
    ): self {
        return self::create(
            AnnouncementType::CheckInClosing,
            $flightDefinitionId,
            $languages,
            $counterRange,
        );
    }

    public static function inviteToBoard(
        string $flightDefinitionId,
        GateCode $gateCode,
        AnnouncementLanguages $languages,
    ): self {
        return self::create(
            AnnouncementType::BoardingInvitation,
            $flightDefinitionId,
            $languages,
            gateCode: $gateCode,
        );
    }

    public static function announceArrival(
        string $flightDefinitionId,
        AnnouncementLanguages $languages,
    ): self {
        return self::create(
            AnnouncementType::Arrival,
            $flightDefinitionId,
            $languages,
        );
    }

    public function cancel(): bool
    {
        if (AnnouncementStatus::Cancelled === $this->status) {
            return false;
        }

        $this->status = AnnouncementStatus::Cancelled;
        $this->cancelledAt = self::now();
        $this->recordEvent(new AnnouncementCancelled(
            $this->id->toRfc4122(),
            $this->cancelledAt,
        ));

        return true;
    }

    public function changeLanguages(AnnouncementLanguages $languages): bool
    {
        if (AnnouncementStatus::Cancelled === $this->status) {
            throw AnnouncementLanguagesCannotBeChangedException::forCancelledAnnouncement($this->id->toRfc4122());
        }

        if ($this->getLanguages()->equals($languages)) {
            return false;
        }

        $this->languageCodes = $languages->toStrings();
        $this->recordEvent(new AnnouncementLanguagesChanged(
            $this->id->toRfc4122(),
            $languages->toStrings(),
            self::now(),
        ));

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

    public function getCheckInCounterRange(): ?CheckInCounterRange
    {
        if ($this->checkInCounterStart === null || $this->checkInCounterEnd === null) {
            return null;
        }

        return CheckInCounterRange::between($this->checkInCounterStart, $this->checkInCounterEnd);
    }

    public function getGateCode(): ?GateCode
    {
        return $this->gateCode === null ? null : GateCode::fromString($this->gateCode);
    }

    public function getLanguages(): AnnouncementLanguages
    {
        return AnnouncementLanguages::fromCodes(...array_map(
            static fn (string $code): LanguageCode => LanguageCode::fromString($code),
            $this->languageCodes,
        ));
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

    private static function create(
        AnnouncementType $type,
        string $flightDefinitionId,
        AnnouncementLanguages $languages,
        ?CheckInCounterRange $counterRange = null,
        ?GateCode $gateCode = null,
    ): self {
        if (!Uuid::isValid($flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($flightDefinitionId);
        }

        $now = self::now();
        $announcement = new self();
        $announcement->id = Uuid::v7();
        $announcement->type = $type;
        $announcement->flightDefinitionId = Uuid::fromString($flightDefinitionId);
        $announcement->checkInCounterStart = $counterRange?->start();
        $announcement->checkInCounterEnd = $counterRange?->end();
        $announcement->gateCode = $gateCode?->toString();
        $announcement->languageCodes = $languages->toStrings();
        $announcement->status = AnnouncementStatus::PendingPreparation;
        $announcement->createdAt = $now;
        $announcement->cancelledAt = null;

        $announcement->recordEvent(new AnnouncementCreated(
            $announcement->id->toRfc4122(),
            $type->value,
            $announcement->flightDefinitionId->toRfc4122(),
            $languages->toStrings(),
            $now,
        ));

        return $announcement;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
