<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Authenticated in-browser preview of an audio asset file. Same streaming as the
 * agent's internal download contract (InternalAudioAssetFileController), but under
 * /api/v1 with the admin's JWT — so the web UI can let an operator listen to a
 * generated or uploaded asset before wiring it into a preset or flight template.
 */
#[Route('/admin/audio-assets')]
final readonly class AdminAudioAssetFileController
{
    public function __construct(
        private AudioAssetRepositoryInterface $audioAssets,
        private AudioAssetStorageInterface $storage,
    ) {
    }

    #[Route('/{id}/file', name: 'admin_audio_asset_file', methods: ['GET'])]
    public function download(string $id): StreamedResponse
    {
        if (!Uuid::isValid($id)) {
            throw new NotFoundHttpException('Unknown audio asset.');
        }

        $asset = $this->audioAssets->findById(Uuid::fromString($id));
        if ($asset === null || !$asset->isActive() || $asset->getStorageKey() === null) {
            throw new NotFoundHttpException('Unknown audio asset.');
        }

        $stream = $this->storage->readStream($asset->getStorageKey());
        if ($stream === null) {
            throw new NotFoundHttpException('Audio asset file is missing.');
        }

        return new StreamedResponse(
            static function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            200,
            ['Content-Type' => $asset->getMimeType() ?? 'application/octet-stream'],
        );
    }
}
