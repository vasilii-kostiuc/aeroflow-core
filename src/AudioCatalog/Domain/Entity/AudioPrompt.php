<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Event\AudioPromptActivated;
use App\AudioCatalog\Domain\Event\AudioPromptCreated;
use App\AudioCatalog\Domain\Event\AudioPromptDeactivated;
use App\AudioCatalog\Domain\Event\AudioPromptUpdated;
use App\AudioCatalog\Domain\Exception\InvalidAudioPromptException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audio_prompt')]
#[ORM\UniqueConstraint(name: 'UNIQ_AUDIO_PROMPT_STATE', columns: ['kind', 'value', 'language_code', 'active'])]
final class AudioPrompt extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 32, enumType: AudioPromptKind::class)]
    private AudioPromptKind $kind;

    #[ORM\Column(length: 16)]
    private string $value;

    #[ORM\Column(length: 16)]
    private string $languageCode;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $audioAssetId;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        AudioPromptKind $kind,
        string $value,
        LanguageCode $languageCode,
        Uuid $audioAssetId,
    ): self {
        $now = self::now();
        $prompt = new self();
        $prompt->id = Uuid::v7();
        $prompt->kind = $kind;
        $prompt->value = self::normalizeValue($value);
        $prompt->languageCode = $languageCode->toString();
        $prompt->audioAssetId = $audioAssetId;
        $prompt->active = true;
        $prompt->createdAt = $now;
        $prompt->updatedAt = $now;
        $prompt->recordEvent(new AudioPromptCreated($prompt->id->toRfc4122(), $kind->value, $prompt->value, $prompt->languageCode, $now));

        return $prompt;
    }

    public function update(AudioPromptKind $kind, string $value, LanguageCode $languageCode, Uuid $audioAssetId): bool
    {
        $value = self::normalizeValue($value);
        $language = $languageCode->toString();
        if ($this->kind === $kind && $this->value === $value && $this->languageCode === $language && $this->audioAssetId->equals($audioAssetId)) {
            return false;
        }
        $this->kind = $kind;
        $this->value = $value;
        $this->languageCode = $language;
        $this->audioAssetId = $audioAssetId;
        $this->updatedAt = self::now();
        $this->recordEvent(new AudioPromptUpdated($this->id->toRfc4122(), $this->updatedAt));

        return true;
    }

    public function activate(): bool
    {
        return $this->changeStatus(true);
    }

    public function deactivate(): bool
    {
        return $this->changeStatus(false);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKind(): AudioPromptKind
    {
        return $this->kind;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getAudioAssetId(): Uuid
    {
        return $this->audioAssetId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function changeStatus(bool $active): bool
    {
        if ($this->active === $active) {
            return false;
        }
        $this->active = $active;
        $this->updatedAt = self::now();
        $event = $active ? new AudioPromptActivated($this->id->toRfc4122(), $this->updatedAt) : new AudioPromptDeactivated($this->id->toRfc4122(), $this->updatedAt);
        $this->recordEvent($event);

        return true;
    }

    private static function normalizeValue(string $value): string
    {
        $value = strtoupper(trim((string) preg_replace('/\s+/', ' ', $value)));
        if (!preg_match('/^[A-Z0-9][A-Z0-9 -]{0,15}$/', $value)) {
            throw InvalidAudioPromptException::invalidValue($value);
        }

        return $value;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
