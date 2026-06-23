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

    /** @param array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string} $data */
    public static function create(AnnouncementVariant $variant, FlightAnnouncementType $announcementType, array $data): self
    {
        $segment = new self();
        $segment->id = Uuid::v7();
        $segment->variant = $variant;
        $segment->createdAt = self::now();
        $segment->apply($announcementType, $data);

        return $segment;
    }

    /** @param array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string} $data */
    private function apply(FlightAnnouncementType $announcementType, array $data): void
    {
        if ($data['sortOrder'] < 1) {
            throw InvalidAnnouncementTemplateSegmentException::invalidSortOrder();
        }
        $type = AnnouncementTemplateSegmentType::from($data['type']);
        $this->sortOrder = $data['sortOrder'];
        $this->type = $type;
        $this->audioAssetId = null;
        $this->slot = null;
        $this->durationMs = null;
        $this->text = null;

        if ($type === AnnouncementTemplateSegmentType::AudioAsset) {
            $id = $data['audioAssetId'] ?? null;
            if (!is_string($id) || !Uuid::isValid($id)) {
                throw InvalidAnnouncementTemplateSegmentException::invalidAudioAsset();
            }
            $this->audioAssetId = Uuid::fromString($id);
        } elseif ($type === AnnouncementTemplateSegmentType::DynamicSlot) {
            $slot = DynamicSlotType::from((string) ($data['slot'] ?? ''));
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
            $this->slot = $slot;
        } elseif ($type === AnnouncementTemplateSegmentType::Pause) {
            $duration = $data['durationMs'] ?? null;
            if (!is_int($duration) || $duration < 100 || $duration > 10000) {
                throw InvalidAnnouncementTemplateSegmentException::invalidPause();
            }
            $this->durationMs = $duration;
        } else {
            $text = trim((string) ($data['text'] ?? ''));
            if ($text === '') {
                throw InvalidAnnouncementTemplateSegmentException::invalidText();
            }
            $this->text = $text;
        }
        $this->updatedAt = self::now();
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
}
