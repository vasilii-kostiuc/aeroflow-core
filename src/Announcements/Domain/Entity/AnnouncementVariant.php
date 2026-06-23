<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\AnnouncementTemplateSegmentType;
use App\Announcements\Domain\Enum\DynamicSlotType;
use App\Announcements\Domain\Exception\InvalidAnnouncementTemplateSegmentException;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use ValueError;

#[ORM\Entity]
#[ORM\Table(name: 'announcement_variant')]
final class AnnouncementVariant
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: FlightAnnouncementConfig::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FlightAnnouncementConfig $config;

    #[ORM\Column(length: 16)]
    private string $languageCode;

    #[ORM\Column]
    private int $sortOrder;

    #[ORM\Column]
    private bool $enabled;

    /** @var Collection<int, AnnouncementTemplateSegment> */
    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: AnnouncementTemplateSegment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $segments;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
        $this->segments = new ArrayCollection();
    }

    /** @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments */
    public static function create(FlightAnnouncementConfig $config, LanguageCode $languageCode, int $sortOrder, array $segments, bool $enabled): self
    {
        $variant = new self();
        $variant->id = Uuid::v7();
        $variant->config = $config;
        $variant->languageCode = $languageCode->toString();
        $variant->sortOrder = $sortOrder;
        $variant->enabled = $enabled;
        $variant->createdAt = self::now();
        $variant->replaceSegments($segments);

        return $variant;
    }

    /** @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments */
    public function update(LanguageCode $languageCode, int $sortOrder, array $segments, bool $enabled): bool
    {
        $before = [$this->languageCode, $this->sortOrder, $this->enabled, array_map(fn (AnnouncementTemplateSegment $s): array => $s->toArray(), $this->getSegments())];
        $this->languageCode = $languageCode->toString();
        $this->sortOrder = $sortOrder;
        $this->enabled = $enabled;
        $this->replaceSegments($segments);

        return $before !== [$this->languageCode, $this->sortOrder, $this->enabled, array_map(fn (AnnouncementTemplateSegment $s): array => $s->toArray(), $this->getSegments())];
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<AnnouncementTemplateSegment> */
    public function getSegments(): array
    {
        $segments = $this->segments->toArray();
        usort($segments, static fn (AnnouncementTemplateSegment $a, AnnouncementTemplateSegment $b): int => $a->getSortOrder() <=> $b->getSortOrder());

        return $segments;
    }

    public function requiresTts(): bool
    {
        foreach ($this->segments as $segment) {
            if ($segment->getType()->value === 'text') {
                return true;
            }
        }

        return false;
    }

    /** @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments */
    private function replaceSegments(array $segments): void
    {
        if ($this->sortOrder < 1 || $segments === []) {
            throw new InvalidArgumentException('Variant requires a positive sort order and at least one segment.');
        }
        $orders = array_column($segments, 'sortOrder');
        if (count($orders) !== count(array_unique($orders))) {
            throw new InvalidArgumentException('Segment sort orders must be unique.');
        }
        $this->segments->clear();
        foreach ($segments as $data) {
            $this->segments->add($this->createSegment($data));
        }
        $this->updatedAt = self::now();
    }

    /** @param array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string} $data */
    private function createSegment(array $data): AnnouncementTemplateSegment
    {
        $type = AnnouncementTemplateSegmentType::from($data['type']);

        return match ($type) {
            AnnouncementTemplateSegmentType::AudioAsset => AnnouncementTemplateSegment::audioAsset(
                $this,
                $data['sortOrder'],
                $this->parseAudioAssetId($data['audioAssetId'] ?? null),
            ),
            AnnouncementTemplateSegmentType::DynamicSlot => AnnouncementTemplateSegment::dynamicSlot(
                $this,
                $data['sortOrder'],
                $this->parseDynamicSlot($data['slot'] ?? null),
                $this->config->getAnnouncementType(),
            ),
            AnnouncementTemplateSegmentType::Pause => AnnouncementTemplateSegment::pause(
                $this,
                $data['sortOrder'],
                $this->parsePauseDuration($data['durationMs'] ?? null),
            ),
            AnnouncementTemplateSegmentType::Text => AnnouncementTemplateSegment::text(
                $this,
                $data['sortOrder'],
                (string) ($data['text'] ?? ''),
            ),
        };
    }

    private function parseAudioAssetId(mixed $audioAssetId): Uuid
    {
        if (!is_string($audioAssetId) || !Uuid::isValid($audioAssetId)) {
            throw InvalidAnnouncementTemplateSegmentException::invalidAudioAsset();
        }

        return Uuid::fromString($audioAssetId);
    }

    private function parseDynamicSlot(mixed $slot): DynamicSlotType
    {
        if (!is_string($slot)) {
            throw InvalidAnnouncementTemplateSegmentException::invalidSlot('');
        }

        try {
            return DynamicSlotType::from($slot);
        } catch (ValueError) {
            throw InvalidAnnouncementTemplateSegmentException::invalidSlot($slot);
        }
    }

    private function parsePauseDuration(mixed $durationMs): int
    {
        if (!is_int($durationMs)) {
            throw InvalidAnnouncementTemplateSegmentException::invalidPause();
        }

        return $durationMs;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
