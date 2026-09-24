<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Api\Request\MoveAudioAssetsRequest;
use App\AudioCatalog\Api\Request\UpdateAudioAssetRequest;
use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\GenerateAudioAsset\GenerateAudioAssetCommand;
use App\AudioCatalog\Application\ListAudioAssets\ListAudioAssetsQuery;
use App\AudioCatalog\Application\MoveAudioAssets\MoveAudioAssetsCommand;
use App\AudioCatalog\Application\UpdateAudioAsset\UpdateAudioAssetCommand;
use App\AudioCatalog\Application\UploadAudioAsset\UploadAudioAssetCommand;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
                        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Выход A12', description: 'Defaults to the file name without extension'),
                        new OA\Property(property: 'folderId', type: 'string', format: 'uuid', nullable: true),
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
            new OA\Response(response: 404, description: 'Folder not found'),
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
            $this->optionalString($request->request->get('title')),
            $this->optionalString($request->request->get('folderId')),
        ), AudioAssetResult::class);

        return ApiResponse::created($result, 'Audio asset uploaded successfully');
    }

    #[Route(':generate', methods: ['POST'])]
    #[OA\Post(
        summary: 'Generate an audio asset from text via TTS',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['text', 'languageCode'],
                properties: [
                    new OA\Property(property: 'text', type: 'string', example: 'Рейс 214 приглашается на посадку'),
                    new OA\Property(property: 'languageCode', type: 'string', example: 'ru'),
                    new OA\Property(property: 'voice', type: 'string', nullable: true, example: 'dmitri'),
                    new OA\Property(property: 'folderId', type: 'string', format: 'uuid', nullable: true),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Audio asset generated (or reused from cache)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: new Model(type: AudioAssetResult::class)),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Folder not found'),
            new OA\Response(response: 422, description: 'Empty text or unsupported language/voice'),
            new OA\Response(response: 502, description: 'TTS service unavailable'),
        ],
    )]
    public function generate(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $voice = $payload['voice'] ?? null;

        $result = $this->bus->handleAs(new GenerateAudioAssetCommand(
            (string) ($payload['text'] ?? ''),
            (string) ($payload['languageCode'] ?? ''),
            is_string($voice) ? $voice : null,
            $this->optionalString($payload['folderId'] ?? null),
        ), AudioAssetResult::class);

        return ApiResponse::created($result, 'Audio asset generated successfully');
    }

    #[Route(':move', methods: ['POST'])]
    #[OA\Post(
        summary: 'Move several audio assets into one folder, all or nothing',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Moved audio assets',
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
            new OA\Response(response: 404, description: 'An audio asset or the folder not found; nothing moved'),
            new OA\Response(response: 422, description: 'No or too many assets'),
        ],
    )]
    public function move(#[MapRequestPayload] MoveAudioAssetsRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(
            new MoveAudioAssetsCommand(array_values($r->assetIds), $r->folderId),
            AudioAssetResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Rename an audio asset or move it to another folder',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Audio asset updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: new Model(type: AudioAssetResult::class)),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Audio asset or folder not found'),
            new OA\Response(response: 422, description: 'Empty or too long title'),
        ],
    )]
    public function update(string $id, #[MapRequestPayload] UpdateAudioAssetRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateAudioAssetCommand($id, $r->title, $r->folderId),
            AudioAssetResult::class,
        ));
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? $value : null;
    }
}
