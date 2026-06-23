<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Entity;

use App\Announcements\Domain\ValueObject\LanguageCode;
use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audio_asset')]
#[ORM\Index(name: 'IDX_AUDIO_ASSET_ACTIVE_NAME', columns: ['active', 'name'])]
final class AudioAsset extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 16)]
    private string $languageCode;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $storageKey;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(nullable: true)]
    private ?int $sizeBytes;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function register(string $name, LanguageCode $languageCode): self
    {
        $normalizedName = trim($name);
        if ('' === $normalizedName) {
            throw new InvalidArgumentException('Audio asset name cannot be empty.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $asset = new self();
        $asset->id = Uuid::v7();
        $asset->name = $normalizedName;
        $asset->languageCode = $languageCode->toString();
        $asset->storageKey = null;
        $asset->mimeType = null;
        $asset->sizeBytes = null;
        $asset->active = true;
        $asset->createdAt = $now;
        $asset->updatedAt = $now;

        return $asset;
    }

    public static function upload(
        string $name,
        LanguageCode $languageCode,
        string $storageKey,
        string $mimeType,
        int $sizeBytes,
    ): self {
        $asset = self::register($name, $languageCode);
        $asset->storageKey = $storageKey;
        $asset->mimeType = $mimeType;
        $asset->sizeBytes = $sizeBytes;
        $asset->recordEvent(new AudioAssetUploaded(
            $asset->id->toRfc4122(),
            $asset->name,
            $asset->languageCode,
            $mimeType,
            $sizeBytes,
            $asset->createdAt,
        ));

        return $asset;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getStorageKey(): ?string
    {
        return $this->storageKey;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): ?int
    {
        return $this->sizeBytes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
