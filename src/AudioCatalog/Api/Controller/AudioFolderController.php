<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Api\Request\AudioFolderRequest;
use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Application\CreateAudioFolder\CreateAudioFolderCommand;
use App\AudioCatalog\Application\DeleteAudioFolder\DeleteAudioFolderCommand;
use App\AudioCatalog\Application\ListAudioFolders\ListAudioFoldersQuery;
use App\AudioCatalog\Application\UpdateAudioFolder\UpdateAudioFolderCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/** Folders of the audio library (task 031): organisation for admins, no business meaning. */
#[Route('/admin/audio-folders')]
#[OA\Tag(name: 'Audio folders')]
final readonly class AudioFolderController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'All folders as a flat list; the tree is built from parentId',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: AudioFolderResult::class)),
                ),
            ],
            type: 'object',
        ),
    )]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListAudioFoldersQuery(), AudioFolderResult::class));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Folder created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioFolderResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Parent folder not found')]
    #[OA\Response(response: 409, description: 'Name already taken under this parent')]
    #[OA\Response(response: 422, description: 'Invalid name or too deep')]
    public function create(#[MapRequestPayload] AudioFolderRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateAudioFolderCommand($r->name, $r->parentId),
            AudioFolderResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'Folder renamed and/or moved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioFolderResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Folder or parent not found')]
    #[OA\Response(response: 409, description: 'Name already taken under this parent')]
    #[OA\Response(response: 422, description: 'Invalid name, too deep, or moving into itself or a subfolder')]
    public function update(string $id, #[MapRequestPayload] AudioFolderRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateAudioFolderCommand($id, $r->name, $r->parentId),
            AudioFolderResult::class,
        ));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Folder deleted')]
    #[OA\Response(response: 404, description: 'Folder not found')]
    #[OA\Response(response: 409, description: 'Folder still has subfolders or active assets')]
    public function delete(string $id): JsonResponse
    {
        $this->bus->dispatch(new DeleteAudioFolderCommand($id));

        return ApiResponse::noContent();
    }
}
