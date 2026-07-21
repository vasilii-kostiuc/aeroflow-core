<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\GenerateAudioAsset;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Port\Tts\TextToSpeechPort;
use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Exception\InvalidSynthesisRequestException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use RuntimeException;
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
    private const MAX_TEXT_LENGTH = 2000;

    /**
     * @var array<string, string>
     */
    private const EXTENSION_BY_MIME_TYPE = [
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/mpeg' => 'mp3',
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
    ];

    public function __construct(
        private AudioAssetRepositoryInterface $repository,
        private AudioAssetStorageInterface $storage,
        private TextToSpeechPort $tts,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(GenerateAudioAssetCommand $command): AudioAssetResult
    {
        $text = trim($command->text);
        if ('' === $text) {
            throw InvalidSynthesisRequestException::emptyText();
        }
        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            throw InvalidSynthesisRequestException::textTooLong(self::MAX_TEXT_LENGTH);
        }

        $language = LanguageCode::fromString($command->languageCode);
        $languageCode = $language->toString();

        $voiceRequest = null !== $command->voice ? trim($command->voice) : null;
        $voice = $this->tts->describeVoice($languageCode, '' === $voiceRequest ? null : $voiceRequest);

        $textHash = hash('sha256', $text);
        $candidates = $this->repository->findActiveGeneratedByContent($textHash, $languageCode, $voice->voice);

        foreach ($candidates as $candidate) {
            if ($candidate->getTtsModelVersion() === $voice->modelVersion) {
                return AudioAssetResult::fromEntity($candidate);
            }
        }

        $audio = $this->tts->synthesize($text, $languageCode, $voice->voice);
        $extension = self::EXTENSION_BY_MIME_TYPE[$audio->mimeType] ?? 'wav';

        $spooledPath = $this->spool($audio->bytes);
        try {
            $storageKey = $this->storage->store($spooledPath, $extension);
        } finally {
            @unlink($spooledPath);
        }

        try {
            $asset = AudioAsset::generate(
                $this->buildName($text, $languageCode, $extension),
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

    /**
     * Writes synthesized bytes to a temporary file so the opaque storage can copy
     * them in, then removes it. Storage stays path/stream oriented as for uploads.
     */
    private function spool(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tts-');
        if (false === $path || false === file_put_contents($path, $bytes)) {
            throw new RuntimeException('Unable to spool synthesized audio to a temporary file.');
        }

        return $path;
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
