<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UploadAudioAsset;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Support\StoredAudioFile;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\ValueObject\AudioFormat;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use finfo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UploadAudioAssetHandler
{
    private const MAX_SIZE_BYTES = 50 * 1024 * 1024;

    public function __construct(
        private AudioAssetRepositoryInterface $repository,
        private StoredAudioFile $storedFile,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UploadAudioAssetCommand $command): AudioAssetResult
    {
        if (!is_file($command->temporaryPath) || !is_readable($command->temporaryPath)) {
            throw InvalidAudioAssetUploadException::unreadable();
        }
        if ($command->sizeBytes < 1) {
            throw InvalidAudioAssetUploadException::emptyFile();
        }
        if ($command->sizeBytes > self::MAX_SIZE_BYTES) {
            throw InvalidAudioAssetUploadException::tooLarge(self::MAX_SIZE_BYTES);
        }

        $originalName = basename(str_replace('\\', '/', trim($command->originalName)));
        if ('' === $originalName || mb_strlen($originalName) > 255) {
            throw InvalidAudioAssetUploadException::invalidName();
        }

        $detectedMimeType = new finfo(FILEINFO_MIME_TYPE)->file($command->temporaryPath);
        $format = is_string($detectedMimeType) ? AudioFormat::tryFromMimeType($detectedMimeType) : null;
        if (null === $format) {
            throw InvalidAudioAssetUploadException::unsupportedFormat(is_string($detectedMimeType) ? $detectedMimeType : 'unknown');
        }

        $asset = $this->storedFile->fromFile(
            $command->temporaryPath,
            $format->extension,
            function (string $storageKey) use ($command, $originalName, $format): AudioAsset {
                $asset = AudioAsset::upload(
                    $originalName,
                    LanguageCode::fromString($command->languageCode),
                    $storageKey,
                    $format->mimeType,
                    $command->sizeBytes,
                );
                $this->repository->save($asset);

                return $asset;
            },
        );

        $this->events->publish(...$asset->pullEvents());

        return AudioAssetResult::fromEntity($asset);
    }
}
