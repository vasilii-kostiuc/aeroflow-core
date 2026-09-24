<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Enum\AudioAssetSource;
use App\AudioCatalog\Domain\Event\AudioAssetGenerated;
use App\AudioCatalog\Domain\Event\AudioAssetUpdated;
use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audio_asset')]
#[ORM\Index(name: 'IDX_AUDIO_ASSET_ACTIVE_NAME', columns: ['active', 'name'])]
#[ORM\Index(name: 'IDX_AUDIO_ASSET_TTS_CACHE', columns: ['source', 'active', 'tts_text_hash', 'language_code', 'tts_voice'])]
#[ORM\Index(name: 'IDX_AUDIO_ASSET_FOLDER', columns: ['folder_id'])]
final class AudioAsset extends AggregateRoot
{
    public const int MAX_TITLE_LENGTH = 255;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Technical file name; immutable, shown only as a secondary line in the UI. */
    #[ORM\Column(length: 255)]
    private string $name;

    /** Human-readable title for the UI (task 031), e.g. «Выход A12». */
    #[ORM\Column(length: self::MAX_TITLE_LENGTH)]
    private string $title;

    /** Library folder as a bare id (rule #6); null means «Без папки». */
    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $folderId;

    #[ORM\Column(length: 16)]
    private string $languageCode;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $storageKey;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(nullable: true)]
    private ?int $sizeBytes;

    #[ORM\Column(length: 16, enumType: AudioAssetSource::class)]
    private AudioAssetSource $source;

    /**
     * TTS cache attributes — set only for {@see AudioAssetSource::Generated}
     * assets, null otherwise. Together with `languageCode` they let Core reuse an
     * already-synthesized asset instead of calling the TTS service again, and
     * detect a model-version change (same content, newer voice model).
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ttsTextHash;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $ttsVoice;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $ttsModelVersion;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    /**
     * @param string|null $title defaults to the file name without its extension,
     *                           with `-` and `_` turned into spaces
     */
    public static function register(
        string $name,
        LanguageCode $languageCode,
        ?string $title = null,
        ?Uuid $folderId = null,
    ): self {
        $normalizedName = trim($name);
        if ('' === $normalizedName) {
            throw new InvalidArgumentException('Audio asset name cannot be empty.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $asset = new self();
        $asset->id = Uuid::v7();
        $asset->name = $normalizedName;
        $asset->title = self::normalizeTitle($title ?? self::titleFromFileName($normalizedName));
        $asset->folderId = $folderId;
        $asset->languageCode = $languageCode->toString();
        $asset->storageKey = null;
        $asset->mimeType = null;
        $asset->sizeBytes = null;
        $asset->source = AudioAssetSource::Uploaded;
        $asset->ttsTextHash = null;
        $asset->ttsVoice = null;
        $asset->ttsModelVersion = null;
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
        ?string $title = null,
        ?Uuid $folderId = null,
    ): self {
        $asset = self::register($name, $languageCode, $title, $folderId);
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

    public static function generate(
        string $name,
        LanguageCode $languageCode,
        string $storageKey,
        string $mimeType,
        int $sizeBytes,
        string $textHash,
        string $voice,
        string $modelVersion,
        ?string $title = null,
        ?Uuid $folderId = null,
    ): self {
        $asset = self::register($name, $languageCode, $title, $folderId);
        $asset->storageKey = $storageKey;
        $asset->mimeType = $mimeType;
        $asset->sizeBytes = $sizeBytes;
        $asset->source = AudioAssetSource::Generated;
        $asset->ttsTextHash = $textHash;
        $asset->ttsVoice = $voice;
        $asset->ttsModelVersion = $modelVersion;
        $asset->recordEvent(new AudioAssetGenerated(
            $asset->id->toRfc4122(),
            $asset->name,
            $asset->languageCode,
            $mimeType,
            $sizeBytes,
            $voice,
            $modelVersion,
            $asset->createdAt,
        ));

        return $asset;
    }

    /**
     * Changes what people see and where the asset lives in the library. Neither
     * affects existing announcements: their AudioSequence references the asset id.
     * Folder existence is checked by the application layer. Idempotent.
     */
    public function updateDetails(string $title, ?Uuid $folderId): bool
    {
        $title = self::normalizeTitle($title);
        $sameFolder = null === $this->folderId || null === $folderId
            ? $this->folderId === $folderId
            : $this->folderId->equals($folderId);
        if ($this->title === $title && $sameFolder) {
            return false;
        }
        $this->title = $title;
        $this->folderId = $folderId;
        $this->updatedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->recordEvent(new AudioAssetUpdated(
            $this->id->toRfc4122(),
            $this->title,
            $this->folderId?->toRfc4122(),
            $this->updatedAt,
        ));

        return true;
    }

    /**
     * Default title for a file-backed asset: `gate-a12-en.wav` -> `gate a12 en`.
     * Falls back to the whole name when nothing is left after stripping.
     */
    public static function titleFromFileName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $title = trim((string) preg_replace('/[\s_-]+/u', ' ', $base));

        return mb_substr('' === $title ? $name : $title, 0, self::MAX_TITLE_LENGTH);
    }

    /**
     * Retires an asset without physically removing it, so existing announcements
     * that reference it keep resolving. Used when a newer voice model supersedes
     * a previously generated asset. Idempotent.
     */
    public function deactivate(): void
    {
        if (!$this->active) {
            return;
        }
        $this->active = false;
        $this->updatedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFolderId(): ?Uuid
    {
        return $this->folderId;
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

    public function getSource(): AudioAssetSource
    {
        return $this->source;
    }

    public function getTtsTextHash(): ?string
    {
        return $this->ttsTextHash;
    }

    public function getTtsVoice(): ?string
    {
        return $this->ttsVoice;
    }

    public function getTtsModelVersion(): ?string
    {
        return $this->ttsModelVersion;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function normalizeTitle(string $title): string
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));
        if ('' === $title) {
            throw InvalidAudioAssetException::emptyTitle();
        }
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw InvalidAudioAssetException::titleTooLong(self::MAX_TITLE_LENGTH);
        }

        return $title;
    }
}
