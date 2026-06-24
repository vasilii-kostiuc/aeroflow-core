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
    public function create(#[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateAudioPromptCommand($r->kind, $r->value, $r->languageCode, $r->audioAssetId),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(string $id, #[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateAudioPromptCommand($id, $r->kind, $r->value, $r->languageCode, $r->audioAssetId),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeAudioPromptStatusCommand($id, true),
            AudioPromptResult::class,
        ));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeAudioPromptStatusCommand($id, false),
            AudioPromptResult::class,
        ));
    }
}
