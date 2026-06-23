<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceSnapshot;
use App\Announcements\Application\Service\AnnouncementTemplateResolver;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\MissingAudioPromptsException;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AnnouncementTemplateResolverTest extends TestCase
{
    public function testResolvesIndependentCountersInSelectedOrder(): void
    {
        $config = $this->config();
        $audio = $this->createStub(AudioPromptLookupInterface::class);
        $audio->method('activeAssetId')->willReturnCallback(
            static fn (string $kind, string $value, string $language): string => $kind.'-'.$value.'-'.$language,
        );

        $sequence = new AnnouncementTemplateResolver($audio)->resolve(
            $config,
            ['en'],
            [
                new OperationalResourceSnapshot(Uuid::v7()->toRfc4122(), '1'),
                new OperationalResourceSnapshot(Uuid::v7()->toRfc4122(), '3'),
                new OperationalResourceSnapshot(Uuid::v7()->toRfc4122(), '5'),
            ],
            null,
        );

        self::assertSame([
            'check_in_counter_code-1-en',
            'check_in_counter_code-3-en',
            'check_in_counter_code-5-en',
        ], array_column($sequence[0]['items'], 'audioAssetId'));
    }

    public function testRejectsMissingPromptWithoutReturningPartialSequence(): void
    {
        $audio = $this->createStub(AudioPromptLookupInterface::class);
        $audio->method('activeAssetId')->willReturn(null);
        $this->expectException(MissingAudioPromptsException::class);

        new AnnouncementTemplateResolver($audio)->resolve(
            $this->config(),
            ['en'],
            [new OperationalResourceSnapshot(Uuid::v7()->toRfc4122(), '3')],
            null,
        );
    }

    private function config(): FlightAnnouncementConfig
    {
        $config = FlightAnnouncementConfig::create(Uuid::v7()->toRfc4122(), FlightAnnouncementType::CheckInOpening, true, null);
        $config->addVariant(LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 1, 'type' => 'dynamic_slot', 'slot' => 'check_in_counters'],
        ], true);

        return $config;
    }
}
