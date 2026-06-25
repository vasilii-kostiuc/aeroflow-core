<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\ListAudioAssets\ListAudioAssetsQuery;
use App\AudioCatalog\Application\UploadAudioAsset\UploadAudioAssetCommand;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audio-assets')]
#[OA\Tag(name: 'Audio assets')]
final readonly class AudioAssetController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'List audio assets',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Audio assets',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: AudioAssetResult::class)),
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListAudioAssetsQuery(), AudioAssetResult::class));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Upload an audio asset',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'languageCode'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'languageCode', type: 'string', example: 'en'),
                    ],
                    type: 'object',
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Audio asset uploaded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: new Model(type: AudioAssetResult::class)),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 422, description: 'Unreadable upload or validation error'),
        ],
    )]
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
