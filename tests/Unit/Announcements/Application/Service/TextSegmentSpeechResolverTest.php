<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\SpeechAssetGeneratorInterface;
use App\Announcements\Application\Service\TextSegmentSpeechResolver;
use PHPUnit\Framework\TestCase;

final class TextSegmentSpeechResolverTest extends TestCase
{
    public function testGeneratesSpeechForTextSegmentAndEnrichesAssetId(): void
    {
        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::once())
            ->method('generate')
            ->with('Boarding now', 'en')
            ->willReturn('asset-1');

        $result = new TextSegmentSpeechResolver($generator)->resolve([
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => 'keep-me'],
            ['sortOrder' => 2, 'type' => 'text', 'text' => 'Boarding now'],
            ['sortOrder' => 3, 'type' => 'pause', 'durationMs' => 500],
        ], 'en');

        self::assertSame('asset-1', $result[1]['audioAssetId']);
        self::assertSame('keep-me', $result[0]['audioAssetId']);
        self::assertArrayNotHasKey('audioAssetId', $result[2]);
    }

    public function testTrimsTextBeforeGenerating(): void
    {
        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::once())
            ->method('generate')
            ->with('Boarding now', 'ro-MD')
            ->willReturn('asset-2');

        new TextSegmentSpeechResolver($generator)->resolve(
            [['sortOrder' => 1, 'type' => 'text', 'text' => '  Boarding now  ']],
            'ro-MD',
        );
    }

    public function testDoesNotGenerateForEmptyText(): void
    {
        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::never())->method('generate');

        $result = new TextSegmentSpeechResolver($generator)->resolve(
            [['sortOrder' => 1, 'type' => 'text', 'text' => '   ']],
            'en',
        );

        self::assertArrayNotHasKey('audioAssetId', $result[0]);
    }

    public function testLeavesNonTextSegmentsUntouched(): void
    {
        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::never())->method('generate');

        $segments = [
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => 'a'],
            ['sortOrder' => 2, 'type' => 'dynamic_slot', 'slot' => 'gate_code'],
            ['sortOrder' => 3, 'type' => 'pause', 'durationMs' => 500],
        ];

        self::assertSame($segments, new TextSegmentSpeechResolver($generator)->resolve($segments, 'en'));
    }
}
