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
 * Download contract for aeroflow-agent (task 016): streams the file of one active
 * audio asset by its id, so the agent's local cache never needs to know storage
 * keys or paths — the asset id stays the only cross-service identifier. Reading
 * goes through AudioAssetStorageInterface, so a future S3 (or other) backend only
 * adds a storage implementation without touching this endpoint or the agent.
 *
 * Service-to-service endpoint outside /api/v1: no user JWT. There is no
 * inter-service authorization yet (documented open question) — the endpoint relies
 * on network-level trust of the local installation, a known deliberate compromise.
 */
final readonly class InternalAudioAssetFileController
{
    public function __construct(
        private AudioAssetRepositoryInterface $audioAssets,
        private AudioAssetStorageInterface $storage,
    ) {
    }

    #[Route('/internal/v1/audio-assets/{id}/file', name: 'internal_audio_asset_file', methods: ['GET'])]
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
