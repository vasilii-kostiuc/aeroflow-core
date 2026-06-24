<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\ListAudioAssets\ListAudioAssetsQuery;
use App\AudioCatalog\Application\UploadAudioAsset\UploadAudioAssetCommand;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audio-assets')]
final readonly class AudioAssetController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListAudioAssetsQuery(), AudioAssetResult::class));
    }

    #[Route('', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw InvalidAudioAssetUploadException::unreadable();
        }

        $result = $this->bus->handleAs(new UploadAudioAssetCommand(
            $file->getPathname(),
            $file->getClientOriginalName(),
            (string) $request->request->get('languageCode', ''),
            (int) $file->getSize(),
        ), AudioAssetResult::class);

        return ApiResponse::created($result, 'Audio asset uploaded successfully');
    }
}
