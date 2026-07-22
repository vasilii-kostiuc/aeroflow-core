<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Entity;

use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Event\AnnouncementTemplateSegmentAdded;
use App\Announcements\Domain\Event\AnnouncementTemplateSegmentRemoved;
use App\Announcements\Domain\Event\AnnouncementTemplateSegmentUpdated;
use App\Announcements\Domain\Event\AnnouncementVariantAdded;
use App\Announcements\Domain\Event\AnnouncementVariantDisabled;
use App\Announcements\Domain\Event\AnnouncementVariantEnabled;
use App\Announcements\Domain\Event\AnnouncementVariantRemoved;
use App\Announcements\Domain\Event\AnnouncementVariantUpdated;
use App\Announcements\Domain\Event\FlightAnnouncementConfigCreated;
use App\Announcements\Domain\Event\FlightAnnouncementConfigDisabled;
use App\Announcements\Domain\Event\FlightAnnouncementConfigEnabled;
use App\Announcements\Domain\Event\FlightAnnouncementConfigUpdated;
use App\Announcements\Domain\Exception\AnnouncementVariantNotFoundException;
use App\Announcements\Domain\Exception\DuplicateAnnouncementVariantLanguageException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Exception\InvalidRepeatRuleException;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'flight_announcement_config')]
#[ORM\UniqueConstraint(name: 'UNIQ_FLIGHT_ANNOUNCEMENT_CONFIG_TYPE', columns: ['flight_definition_id', 'announcement_type'])]
#[ORM\Index(name: 'IDX_FLIGHT_ANNOUNCEMENT_CONFIG_FLIGHT', columns: ['flight_definition_id'])]
final class FlightAnnouncementConfig extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $flightDefinitionId;

    #[ORM\Column(length: 32, enumType: FlightAnnouncementType::class)]
    private FlightAnnouncementType $announcementType;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column(nullable: true)]
    private ?int $repeatEveryMinutes;

    /**
     * @var Collection<int, AnnouncementVariant>
     */
    #[ORM\OneToMany(mappedBy: 'config', targetEntity: AnnouncementVariant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variants;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
        $this->variants = new ArrayCollection();
    }

    public static function create(
        string $flightDefinitionId,
        FlightAnnouncementType $announcementType,
        bool $enabled,
        ?int $repeatEveryMinutes,
    ): self {
        if (!Uuid::isValid($flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($flightDefinitionId);
        }

        self::assertRepeatRule($announcementType, $repeatEveryMinutes);

        $now = self::now();
        $config = new self();
        $config->id = Uuid::v7();
        $config->flightDefinitionId = Uuid::fromString($flightDefinitionId);
        $config->announcementType = $announcementType;
        $config->enabled = $enabled;
        $config->repeatEveryMinutes = $repeatEveryMinutes;
        $config->createdAt = $now;
        $config->updatedAt = $now;

        $config->recordEvent(new FlightAnnouncementConfigCreated(
            $config->id->toRfc4122(),
            $flightDefinitionId,
            $announcementType->value,
            $now,
        ));

        return $config;
    }

    public function changeSettings(bool $enabled, ?int $repeatEveryMinutes): bool
    {
        self::assertRepeatRule($this->announcementType, $repeatEveryMinutes);

        if ($this->enabled === $enabled && $this->repeatEveryMinutes === $repeatEveryMinutes) {
            return false;
        }

        $previousEnabled = $this->enabled;
        $previousRepeat = $this->repeatEveryMinutes;
        $this->enabled = $enabled;
        $this->repeatEveryMinutes = $repeatEveryMinutes;
        $this->updatedAt = self::now();

        $this->recordEvent(new FlightAnnouncementConfigUpdated(
            $this->id->toRfc4122(),
            $this->flightDefinitionId->toRfc4122(),
            $this->announcementType->value,
            array_values(array_filter([
                $previousEnabled !== $enabled ? 'enabled' : null,
                $previousRepeat !== $repeatEveryMinutes ? 'repeatEveryMinutes' : null,
            ])),
            $this->updatedAt,
        ));
        if ($previousEnabled !== $enabled) {
            $eventClass = $enabled ? FlightAnnouncementConfigEnabled::class : FlightAnnouncementConfigDisabled::class;
            $this->recordEvent(new $eventClass(
                $this->id->toRfc4122(),
                $this->flightDefinitionId->toRfc4122(),
                $this->announcementType->value,
                $this->updatedAt,
            ));
        }

        return true;
    }

    /** @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments */
    public function addVariant(
        LanguageCode $languageCode,
        int $sortOrder,
        array $segments,
        bool $enabled,
    ): AnnouncementVariant {
        $this->assertLanguageIsUnique($languageCode, $enabled);

        $variant = AnnouncementVariant::create(
            $this,
            $languageCode,
            $sortOrder,
            $segments,
            $enabled,
        );

        $this->variants->add($variant);
        $this->updatedAt = self::now();
        $this->recordEvent(new AnnouncementVariantAdded(
            $this->id->toRfc4122(),
            $variant->getId()->toRfc4122(),
            $languageCode->toString(),
            'segments',
            $enabled,
            $this->updatedAt,
        ));
        foreach ($variant->getSegments() as $segment) {
            $this->recordEvent(new AnnouncementTemplateSegmentAdded(
                $this->id->toRfc4122(),
                $variant->getId()->toRfc4122(),
                $segment->getId()->toRfc4122(),
                $segment->getType()->value,
                $this->updatedAt,
            ));
        }

        return $variant;
    }

    /** @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments */
    public function updateVariant(
        string $variantId,
        LanguageCode $languageCode,
        int $sortOrder,
        array $segments,
        bool $enabled,
    ): AnnouncementVariant {
        $variant = $this->findVariantOrFail($variantId);
        $this->assertLanguageIsUnique($languageCode, $enabled, $variant->getId());
        $previous = [
            'languageCode' => $variant->getLanguageCode(),
            'sortOrder' => $variant->getSortOrder(),
            'segments' => array_map(static fn (AnnouncementTemplateSegment $segment): array => $segment->toArray(), $variant->getSegments()),
            'enabled' => $variant->isEnabled(),
        ];

        if ($variant->update($languageCode, $sortOrder, $segments, $enabled)) {
            $current = [
                'languageCode' => $variant->getLanguageCode(),
                'sortOrder' => $variant->getSortOrder(),
                'segments' => array_map(static fn (AnnouncementTemplateSegment $segment): array => $segment->toArray(), $variant->getSegments()),
                'enabled' => $variant->isEnabled(),
            ];
            $this->updatedAt = self::now();
            $this->recordEvent(new AnnouncementVariantUpdated(
                $this->id->toRfc4122(),
                $variant->getId()->toRfc4122(),
                $variant->getLanguageCode(),
                'segments',
                array_keys(array_filter(
                    $current,
                    static fn (mixed $value, string $field): bool => $previous[$field] !== $value,
                    ARRAY_FILTER_USE_BOTH,
                )),
                $this->updatedAt,
            ));
            if ($previous['enabled'] !== $enabled) {
                $eventClass = $enabled ? AnnouncementVariantEnabled::class : AnnouncementVariantDisabled::class;
                $this->recordEvent(new $eventClass(
                    $this->id->toRfc4122(),
                    $variant->getId()->toRfc4122(),
                    $variant->getLanguageCode(),
                    'segments',
                    $this->updatedAt,
                ));
            }
            foreach ($variant->getSegments() as $segment) {
                $this->recordEvent(new AnnouncementTemplateSegmentUpdated(
                    $this->id->toRfc4122(),
                    $variant->getId()->toRfc4122(),
                    $segment->getId()->toRfc4122(),
                    $segment->getType()->value,
                    $this->updatedAt,
                ));
            }
        }

        return $variant;
    }

    public function removeVariant(string $variantId): bool
    {
        $variant = $this->findVariantOrFail($variantId);
        $segments = $variant->getSegments();

        $removed = $this->variants->removeElement($variant);
        if ($removed) {
            $this->updatedAt = self::now();
            $this->recordEvent(new AnnouncementVariantRemoved(
                $this->id->toRfc4122(),
                $variant->getId()->toRfc4122(),
                $variant->getLanguageCode(),
                'segments',
                $this->updatedAt,
            ));
            foreach ($segments as $segment) {
                $this->recordEvent(new AnnouncementTemplateSegmentRemoved(
                    $this->id->toRfc4122(),
                    $variant->getId()->toRfc4122(),
                    $segment->getId()->toRfc4122(),
                    $segment->getType()->value,
                    $this->updatedAt,
                ));
            }
        }

        return $removed;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFlightDefinitionId(): Uuid
    {
        return $this->flightDefinitionId;
    }

    public function getAnnouncementType(): FlightAnnouncementType
    {
        return $this->announcementType;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getRepeatEveryMinutes(): ?int
    {
        return $this->repeatEveryMinutes;
    }

    /**
     * @return list<AnnouncementVariant>
     */
    public function getVariants(): array
    {
        $variants = $this->variants->toArray();
        usort($variants, static fn (AnnouncementVariant $left, AnnouncementVariant $right): int => $left->getSortOrder() <=> $right->getSortOrder());

        return $variants;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return list<string>
     */
    public function validationErrors(bool $directionCompatible = true): array
    {
        $errors = [];

        if (!$this->enabled) {
            $errors[] = 'configuration_disabled';
        }
        if (!$directionCompatible) {
            $errors[] = 'announcement_type_incompatible_with_flight_direction';
        }

        $activeVariants = array_filter(
            $this->getVariants(),
            static fn (AnnouncementVariant $variant): bool => $variant->isEnabled(),
        );
        if ([] === $activeVariants) {
            $errors[] = 'no_active_variants';
        }
        foreach ($activeVariants as $variant) {
            if ($variant->requiresTts() && !$variant->isTtsSegmentsResolved()) {
                $errors[] = 'text_segment_requires_tts';
                break;
            }
        }

        return $errors;
    }

    private static function assertRepeatRule(FlightAnnouncementType $announcementType, ?int $repeatEveryMinutes): void
    {
        if ($repeatEveryMinutes !== null && !$announcementType->requiresRepeatRule()) {
            throw InvalidRepeatRuleException::forType(FlightAnnouncementType::CheckInContinuation->value);
        }

        if ($repeatEveryMinutes !== null && ($repeatEveryMinutes < 1 || $repeatEveryMinutes > 120)) {
            throw InvalidRepeatRuleException::invalidInterval();
        }
    }

    private function assertLanguageIsUnique(LanguageCode $languageCode, bool $enabled, ?Uuid $excludeId = null): void
    {
        if (!$enabled) {
            return;
        }

        foreach ($this->variants as $variant) {
            if ($excludeId !== null && $variant->getId()->equals($excludeId)) {
                continue;
            }

            if ($variant->isEnabled() && $variant->getLanguageCode() === $languageCode->toString()) {
                throw DuplicateAnnouncementVariantLanguageException::forLanguage($languageCode->toString());
            }
        }
    }

    private function findVariantOrFail(string $variantId): AnnouncementVariant
    {
        if (!Uuid::isValid($variantId)) {
            throw AnnouncementVariantNotFoundException::withId($variantId);
        }

        foreach ($this->variants as $variant) {
            if ($variant->getId()->equals(Uuid::fromString($variantId))) {
                return $variant;
            }
        }

        throw AnnouncementVariantNotFoundException::withId($variantId);
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
