<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Application;

use App\Announcements\Application\ListConfiguredAnnouncementLanguages\ListConfiguredAnnouncementLanguagesHandler;
use App\Announcements\Application\ListConfiguredAnnouncementLanguages\ListConfiguredAnnouncementLanguagesQuery;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListConfiguredAnnouncementLanguagesHandlerTest extends TestCase
{
    private const string FLIGHT_ID = '01900000-0000-7000-8000-000000000001';

    public function testReturnsEnabledVariantLanguagesInSortOrder(): void
    {
        $config = FlightAnnouncementConfig::create(self::FLIGHT_ID, FlightAnnouncementType::CheckInOpening, true, null);
        $config->addVariant(LanguageCode::fromString('en'), 2, [['sortOrder' => 1, 'type' => 'text', 'text' => 'b']], true);
        $config->addVariant(LanguageCode::fromString('ro-MD'), 1, [['sortOrder' => 1, 'type' => 'text', 'text' => 'a']], true);
        $config->addVariant(LanguageCode::fromString('ru'), 3, [['sortOrder' => 1, 'type' => 'text', 'text' => 'c']], false);

        $result = $this->handle($config, self::FLIGHT_ID, 'check_in_opening');

        self::assertSame(['ro-MD', 'en'], $result->languages);
    }

    public function testReturnsEmptyWhenConfigDisabled(): void
    {
        $config = FlightAnnouncementConfig::create(self::FLIGHT_ID, FlightAnnouncementType::CheckInOpening, false, null);
        $config->addVariant(LanguageCode::fromString('en'), 1, [['sortOrder' => 1, 'type' => 'text', 'text' => 'a']], true);

        $result = $this->handle($config, self::FLIGHT_ID, 'check_in_opening');

        self::assertSame([], $result->languages);
    }

    public function testReturnsEmptyWhenNoConfigForType(): void
    {
        $result = $this->handle(null, self::FLIGHT_ID, 'boarding_invitation');

        self::assertSame([], $result->languages);
    }

    public function testReturnsEmptyForInvalidInput(): void
    {
        self::assertSame([], $this->handle(null, 'not-a-uuid', 'check_in_opening')->languages);
        self::assertSame([], $this->handle(null, self::FLIGHT_ID, 'unknown_type')->languages);
    }

    private function handle(
        ?FlightAnnouncementConfig $config,
        string $flightDefinitionId,
        string $announcementType,
    ): \App\Announcements\Application\ConfiguredAnnouncementLanguagesResult {
        $repository = new class($config) implements FlightAnnouncementConfigRepositoryInterface {
            public function __construct(private ?FlightAnnouncementConfig $config)
            {
            }

            public function save(FlightAnnouncementConfig $config): void
            {
            }

            public function findById(Uuid $id): ?FlightAnnouncementConfig
            {
                return null;
            }

            public function findByFlightDefinitionId(Uuid $flightDefinitionId): array
            {
                return null === $this->config ? [] : [$this->config];
            }

            public function findOneForFlightAndType(Uuid $flightDefinitionId, FlightAnnouncementType $type): ?FlightAnnouncementConfig
            {
                return $this->config;
            }
        };

        return (new ListConfiguredAnnouncementLanguagesHandler($repository))(
            new ListConfiguredAnnouncementLanguagesQuery($flightDefinitionId, $announcementType),
        );
    }
}
