<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\ListAudioAssets\ListAudioAssetsQuery;
use App\AudioCatalog\Application\UploadAudioAsset\UploadAudioAssetCommand;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\Shared\Api\Response\ApiResponse;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audio-assets')]
final readonly class AudioAssetController
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $result = $this->messageBus
            ->dispatch(new ListAudioAssetsQuery())
            ->last(HandledStamp::class)
            ?->getResult();

        return ApiResponse::success($result);
    }

    #[Route('', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw InvalidAudioAssetUploadException::unreadable();
        }

        $result = $this->messageBus
            ->dispatch(new UploadAudioAssetCommand(
                $file->getPathname(),
                $file->getClientOriginalName(),
                (string) $request->request->get('languageCode', ''),
                (int) $file->getSize(),
            ))
            ->last(HandledStamp::class)
            ?->getResult();

        if (!$result instanceof AudioAssetResult) {
            throw new LogicException('Audio asset upload handler did not return the expected result.');
        }

        return ApiResponse::created($result, 'Audio asset uploaded successfully');
    }
}
