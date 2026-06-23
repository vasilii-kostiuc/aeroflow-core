<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\AnnouncementTemplateSegmentType;
use App\Announcements\Domain\Enum\DynamicSlotType;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\InvalidAnnouncementTemplateSegmentException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'announcement_template_segment')]
final class AnnouncementTemplateSegment
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: AnnouncementVariant::class, inversedBy: 'segments')]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AnnouncementVariant $variant;

    #[ORM\Column]
    private int $sortOrder;

    #[ORM\Column(length: 24, enumType: AnnouncementTemplateSegmentType::class)]
    private AnnouncementTemplateSegmentType $type;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $audioAssetId;

    #[ORM\Column(length: 32, nullable: true, enumType: DynamicSlotType::class)]
    private ?DynamicSlotType $slot;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function audioAsset(AnnouncementVariant $variant, int $sortOrder, Uuid $audioAssetId): self
    {
        $segment = self::initialize($variant, $sortOrder, AnnouncementTemplateSegmentType::AudioAsset);
        $segment->audioAssetId = $audioAssetId;

        return $segment;
    }

    public static function dynamicSlot(
        AnnouncementVariant $variant,
        int $sortOrder,
        DynamicSlotType $slot,
        FlightAnnouncementType $announcementType,
    ): self {
        $segment = self::initialize($variant, $sortOrder, AnnouncementTemplateSegmentType::DynamicSlot);
        $compatible = match ($slot) {
            DynamicSlotType::CheckInCounters => in_array($announcementType, [
                FlightAnnouncementType::CheckInOpening,
                FlightAnnouncementType::CheckInContinuation,
                FlightAnnouncementType::CheckInClosing,
            ], true),
            DynamicSlotType::GateCode => $announcementType === FlightAnnouncementType::BoardingInvitation,
        };
        if (!$compatible) {
            throw InvalidAnnouncementTemplateSegmentException::invalidSlot($slot->value);
        }

        $segment->slot = $slot;

        return $segment;
    }

    public static function pause(AnnouncementVariant $variant, int $sortOrder, int $durationMs): self
    {
        $segment = self::initialize($variant, $sortOrder, AnnouncementTemplateSegmentType::Pause);
        if ($durationMs < 100 || $durationMs > 10000) {
            throw InvalidAnnouncementTemplateSegmentException::invalidPause();
        }

        $segment->durationMs = $durationMs;

        return $segment;
    }

    public static function text(AnnouncementVariant $variant, int $sortOrder, string $text): self
    {
        $segment = self::initialize($variant, $sortOrder, AnnouncementTemplateSegmentType::Text);
        $text = trim($text);
        if ($text === '') {
            throw InvalidAnnouncementTemplateSegmentException::invalidText();
        }

        $segment->text = $text;

        return $segment;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getType(): AnnouncementTemplateSegmentType
    {
        return $this->type;
    }

    public function getAudioAssetId(): ?Uuid
    {
        return $this->audioAssetId;
    }

    public function getSlot(): ?DynamicSlotType
    {
        return $this->slot;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    /** @return array{sortOrder:int,type:string,audioAssetId:?string,slot:?string,durationMs:?int,text:?string} */
    public function toArray(): array
    {
        return [
            'sortOrder' => $this->sortOrder,
            'type' => $this->type->value,
            'audioAssetId' => $this->audioAssetId?->toRfc4122(),
            'slot' => $this->slot?->value,
            'durationMs' => $this->durationMs,
            'text' => $this->text,
        ];
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private static function initialize(
        AnnouncementVariant $variant,
        int $sortOrder,
        AnnouncementTemplateSegmentType $type,
    ): self {
        if ($sortOrder < 1) {
            throw InvalidAnnouncementTemplateSegmentException::invalidSortOrder();
        }

        $now = self::now();
        $segment = new self();
        $segment->id = Uuid::v7();
        $segment->variant = $variant;
        $segment->sortOrder = $sortOrder;
        $segment->type = $type;
        $segment->audioAssetId = null;
        $segment->slot = null;
        $segment->durationMs = null;
        $segment->text = null;
        $segment->createdAt = $now;
        $segment->updatedAt = $now;

        return $segment;
    }
}
