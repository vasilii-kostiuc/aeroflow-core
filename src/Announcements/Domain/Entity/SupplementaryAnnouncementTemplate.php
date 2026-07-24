<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Event\SupplementaryTemplateActivated;
use App\Announcements\Domain\Event\SupplementaryTemplateCreated;
use App\Announcements\Domain\Event\SupplementaryTemplateDeactivated;
use App\Announcements\Domain\Event\SupplementaryTemplateUpdated;
use App\Announcements\Domain\Exception\InvalidSupplementaryTemplateException;
use App\Announcements\Domain\Exception\SupplementaryTemplateLanguageNotConfiguredException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A reusable, admin-managed preset for an airport-wide supplementary announcement
 * (task 024) — the heir of the legacy САО "Дополнительно" set (грипп, такси,
 * безопасность, …). Each preset carries pre-recorded/generated audio per language;
 * the dispatcher launches one by id, picking a subset of its configured languages.
 *
 * Variants are a value collection fully owned by the aggregate, so they are stored
 * as a JSON column rather than a child-entity tree (same pragmatic choice as
 * Announcement.audioSequence). Asset availability is a cross-aggregate invariant
 * checked in the application layer, not here.
 */
#[ORM\Entity]
#[ORM\Table(name: 'supplementary_announcement_template')]
final class SupplementaryAnnouncementTemplate extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    /** @var list<array{languageCode:string,audioAssetId:string}> */
    #[ORM\Column(type: 'json')]
    private array $variants;

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
     * @param list<array{languageCode:string,audioAssetId:string}> $variants
     */
    public static function create(string $name, array $variants): self
    {
        $now = self::now();
        $template = new self();
        $template->id = Uuid::v7();
        $template->name = self::normalizeName($name);
        $template->variants = self::normalizeVariants($variants);
        $template->active = true;
        $template->createdAt = $now;
        $template->updatedAt = $now;
        $template->recordEvent(new SupplementaryTemplateCreated($template->id->toRfc4122(), $template->name, $now));

        return $template;
    }

    /**
     * @param list<array{languageCode:string,audioAssetId:string}> $variants
     */
    public function update(string $name, array $variants): bool
    {
        $name = self::normalizeName($name);
        $variants = self::normalizeVariants($variants);
        if ($this->name === $name && $this->variants === $variants) {
            return false;
        }

        $this->name = $name;
        $this->variants = $variants;
        $this->updatedAt = self::now();
        $this->recordEvent(new SupplementaryTemplateUpdated($this->id->toRfc4122(), $this->name, $this->updatedAt));

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

    /**
     * The audio asset ids for the requested languages, in the given order, ready to
     * be resolved into an AudioSequence at launch. Every requested language must be
     * configured on the template.
     *
     * @param list<string> $languageCodes
     *
     * @return list<array{languageCode:string,audioAssetId:string}>
     */
    public function assetsForLanguages(array $languageCodes): array
    {
        $byLanguage = [];
        foreach ($this->variants as $variant) {
            $byLanguage[$variant['languageCode']] = $variant['audioAssetId'];
        }

        $selected = [];
        foreach ($languageCodes as $languageCode) {
            if (!isset($byLanguage[$languageCode])) {
                throw SupplementaryTemplateLanguageNotConfiguredException::forLanguage($this->id->toRfc4122(), $languageCode);
            }
            $selected[] = ['languageCode' => $languageCode, 'audioAssetId' => $byLanguage[$languageCode]];
        }

        return $selected;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return list<array{languageCode:string,audioAssetId:string}> */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /** @return list<string> */
    public function getLanguageCodes(): array
    {
        return array_map(static fn (array $variant): string => $variant['languageCode'], $this->variants);
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
        $eventClass = $active ? SupplementaryTemplateActivated::class : SupplementaryTemplateDeactivated::class;
        $this->recordEvent(new $eventClass($this->id->toRfc4122(), $this->updatedAt));

        return true;
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidSupplementaryTemplateException::emptyName();
        }

        return $name;
    }

    /**
     * @param list<array<string,mixed>> $variants raw variants, possibly from untyped JSON
     *
     * @return list<array{languageCode:string,audioAssetId:string}>
     */
    private static function normalizeVariants(array $variants): array
    {
        if ([] === $variants) {
            throw InvalidSupplementaryTemplateException::noVariants();
        }

        $seen = [];
        $normalized = [];
        foreach ($variants as $variant) {
            $languageCode = LanguageCode::fromString((string) ($variant['languageCode'] ?? ''))->toString();
            $audioAssetId = (string) ($variant['audioAssetId'] ?? '');
            if (!Uuid::isValid($audioAssetId)) {
                throw InvalidSupplementaryTemplateException::invalidAsset($audioAssetId);
            }
            if (isset($seen[$languageCode])) {
                throw InvalidSupplementaryTemplateException::duplicateLanguage($languageCode);
            }
            $seen[$languageCode] = true;
            $normalized[] = ['languageCode' => $languageCode, 'audioAssetId' => $audioAssetId];
        }

        return $normalized;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
