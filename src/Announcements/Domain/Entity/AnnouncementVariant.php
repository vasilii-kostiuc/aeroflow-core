<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\AnnouncementVariantSourceType;
use App\Announcements\Domain\Exception\InvalidAnnouncementVariantSourceException;
use App\Announcements\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

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

    #[ORM\Column(length: 16, enumType: AnnouncementVariantSourceType::class)]
    private AnnouncementVariantSourceType $sourceType;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $audioAssetId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        FlightAnnouncementConfig $config,
        LanguageCode $languageCode,
        int $sortOrder,
        AnnouncementVariantSourceType $sourceType,
        ?string $audioAssetId,
        ?string $text,
        bool $enabled,
    ): self {
        $variant = new self();
        $variant->id = Uuid::v7();
        $variant->config = $config;
        $variant->languageCode = $languageCode->toString();
        $variant->createdAt = self::now();
        $variant->applyDetails($sortOrder, $sourceType, $audioAssetId, $text, $enabled);

        return $variant;
    }

    public function update(
        LanguageCode $languageCode,
        int $sortOrder,
        AnnouncementVariantSourceType $sourceType,
        ?string $audioAssetId,
        ?string $text,
        bool $enabled,
    ): bool {
        $previous = [
            $this->languageCode,
            $this->sortOrder,
            $this->sourceType,
            $this->audioAssetId?->toRfc4122(),
            $this->text,
            $this->enabled,
        ];

        $this->languageCode = $languageCode->toString();
        $this->applyDetails($sortOrder, $sourceType, $audioAssetId, $text, $enabled);

        return $previous !== [
            $this->languageCode,
            $this->sortOrder,
            $this->sourceType,
            $this->audioAssetId?->toRfc4122(),
            $this->text,
            $this->enabled,
        ];
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

    public function getSourceType(): AnnouncementVariantSourceType
    {
        return $this->sourceType;
    }

    public function getAudioAssetId(): ?Uuid
    {
        return $this->audioAssetId;
    }

    public function getText(): ?string
    {
        return $this->text;
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

    private function applyDetails(
        int $sortOrder,
        AnnouncementVariantSourceType $sourceType,
        ?string $audioAssetId,
        ?string $text,
        bool $enabled,
    ): void {
        if ($sortOrder < 1) {
            throw InvalidAnnouncementVariantSourceException::invalidSortOrder();
        }

        $normalizedText = $text === null ? null : trim($text);
        $normalizedAudioAssetId = $audioAssetId === null ? null : trim($audioAssetId);

        if (AnnouncementVariantSourceType::AudioAsset === $sourceType) {
            if ($normalizedAudioAssetId === null || !Uuid::isValid($normalizedAudioAssetId)) {
                throw InvalidAnnouncementVariantSourceException::missingAudioAsset();
            }

            $this->audioAssetId = Uuid::fromString($normalizedAudioAssetId);
            $this->text = null;
        } else {
            if ($normalizedText === null || $normalizedText === '') {
                throw InvalidAnnouncementVariantSourceException::missingText();
            }

            $this->audioAssetId = null;
            $this->text = $normalizedText;
        }

        $this->sortOrder = $sortOrder;
        $this->sourceType = $sourceType;
        $this->enabled = $enabled;
        $this->updatedAt = self::now();
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
