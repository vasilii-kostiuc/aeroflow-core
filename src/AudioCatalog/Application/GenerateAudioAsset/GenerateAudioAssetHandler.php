<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\GenerateAudioAsset;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Port\Tts\TextToSpeechPort;
use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\ValueObject\AudioFormat;
use App\AudioCatalog\Domain\ValueObject\SynthesisText;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * Turns text into an AudioAsset(source=generated) via the TTS service.
 *
 * The cache lives here, not in the service: an already-generated asset with the
 * same text + language + voice + model version is reused without calling TTS.
 * When only the model version differs, a fresh asset is generated and the stale
 * one is deactivated (not deleted), so existing announcements keep resolving.
 *
 * Generation is atomic: synthesis runs before anything is persisted, so a TTS
 * failure leaves no partial asset behind.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class GenerateAudioAssetHandler
{
    public function __construct(
        private AudioAssetRepositoryInterface $repository,
        private AudioAssetStorageInterface $storage,
        private TextToSpeechPort $tts,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(GenerateAudioAssetCommand $command): AudioAssetResult
    {
        $text = SynthesisText::fromString($command->text);

        $language = LanguageCode::fromString($command->languageCode);
        $languageCode = $language->toString();

        $voiceRequest = null !== $command->voice ? trim($command->voice) : null;
        $voice = $this->tts->describeVoice($languageCode, '' === $voiceRequest ? null : $voiceRequest);

        $textHash = hash('sha256', $text->value);
        $candidates = $this->repository->findActiveGeneratedByContent($textHash, $languageCode, $voice->voice);

        foreach ($candidates as $candidate) {
            if ($candidate->getTtsModelVersion() === $voice->modelVersion) {
                return AudioAssetResult::fromEntity($candidate);
            }
        }

        $audio = $this->tts->synthesize($text->value, $languageCode, $voice->voice);
        $format = AudioFormat::tryFromMimeType($audio->mimeType);
        $extension = null !== $format ? $format->extension : 'wav';

        $storageKey = $this->storage->storeContents($audio->bytes, $extension);

        try {
            $asset = AudioAsset::generate(
                $this->buildName($text->value, $languageCode, $extension),
                $language,
                $storageKey,
                $audio->mimeType,
                strlen($audio->bytes),
                $textHash,
                $voice->voice,
                $voice->modelVersion,
            );
            $this->repository->save($asset);
        } catch (Throwable $exception) {
            $this->storage->delete($storageKey);

            throw $exception;
        }

        // A model upgrade superseded these: keep them for existing announcements
        // but stop them from being reused for new ones.
        foreach ($candidates as $stale) {
            $stale->deactivate();
            $this->repository->save($stale);
        }

        $this->events->publish(...$asset->pullEvents());

        return AudioAssetResult::fromEntity($asset);
    }

    private function buildName(string $text, string $languageCode, string $extension): string
    {
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $text));
        $slug = trim($slug, '-');
        if ('' === $slug) {
            $slug = 'speech';
        }

        return sprintf('tts-%s-%s.%s', $languageCode, mb_substr($slug, 0, 40), $extension);
    }
}
