<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Api\Request\AudioPromptRequest;
use App\AudioCatalog\Application\AudioPromptResult;
use App\AudioCatalog\Application\ChangeAudioPromptStatus\ChangeAudioPromptStatusCommand;
use App\AudioCatalog\Application\CreateAudioPrompt\CreateAudioPromptCommand;
use App\AudioCatalog\Application\ListAudioPrompts\ListAudioPromptsQuery;
use App\AudioCatalog\Application\UpdateAudioPrompt\UpdateAudioPromptCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audio-prompts')]
#[OA\Tag(name: 'Audio prompts')]
final readonly class AudioPromptController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'kind', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['check_in_counter_code', 'gate_code']))]
    #[OA\Parameter(name: 'value', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'languageCode', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: 200,
        description: 'Audio prompts',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: AudioPromptResult::class)),
                ),
            ],
            type: 'object',
        ),
    )]
    public function list(Request $request): JsonResponse
    {
        $active = $request->query->has('active')
            ? filter_var($request->query->get('active'), FILTER_VALIDATE_BOOL)
            : null;

        return ApiResponse::success($this->bus->handleList(new ListAudioPromptsQuery(
            $request->query->getString('kind') ?: null,
            $request->query->getString('value') ?: null,
            $request->query->getString('languageCode') ?: null,
            $active,
        ), AudioPromptResult::class));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Audio prompt created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioPromptResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 409, description: 'Duplicate active audio prompt')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function create(#[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateAudioPromptCommand($r->kind, $r->value, $r->languageCode, $r->audioAssetId),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'Audio prompt updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioPromptResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Audio prompt not found')]
    #[OA\Response(response: 409, description: 'Duplicate active audio prompt')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function update(string $id, #[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateAudioPromptCommand($id, $r->kind, $r->value, $r->languageCode, $r->audioAssetId),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Audio prompt activated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioPromptResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Audio prompt not found')]
    #[OA\Response(response: 409, description: 'Duplicate active audio prompt')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeAudioPromptStatusCommand($id, true),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Audio prompt deactivated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AudioPromptResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Audio prompt not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeAudioPromptStatusCommand($id, false),
            AudioPromptResult::class,
        ));
    }
}
