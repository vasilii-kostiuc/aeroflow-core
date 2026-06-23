<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AudioPromptTest extends TestCase
{
    public function testNormalizesSemanticKeyAndKeepsAssetReference(): void
    {
        $assetId = Uuid::v7();
        $prompt = AudioPrompt::create(
            AudioPromptKind::GateCode,
            ' a5 ',
            LanguageCode::fromString('en'),
            $assetId,
        );
        self::assertSame('A5', $prompt->getValue());
        self::assertTrue($prompt->getAudioAssetId()->equals($assetId));
    }
}
