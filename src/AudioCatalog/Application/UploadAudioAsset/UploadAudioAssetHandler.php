<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UploadAudioAsset;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use finfo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UploadAudioAssetHandler
{
    private const MAX_SIZE_BYTES = 50 * 1024 * 1024;

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

        $mimeType = new finfo(FILEINFO_MIME_TYPE)->file($command->temporaryPath);
        if (!is_string($mimeType) || !isset(self::EXTENSION_BY_MIME_TYPE[$mimeType])) {
            throw InvalidAudioAssetUploadException::unsupportedFormat(is_string($mimeType) ? $mimeType : 'unknown');
        }

        $storageKey = $this->storage->store(
            $command->temporaryPath,
            self::EXTENSION_BY_MIME_TYPE[$mimeType],
        );

        try {
            $asset = AudioAsset::upload(
                $originalName,
                LanguageCode::fromString($command->languageCode),
                $storageKey,
                $mimeType,
                $command->sizeBytes,
            );
            $this->repository->save($asset);
        } catch (Throwable $exception) {
            $this->storage->delete($storageKey);

            throw $exception;
        }

        $this->events->publish(...$asset->pullEvents());

        return AudioAssetResult::fromEntity($asset);
    }
}
