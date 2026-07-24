<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\LaunchSupplementaryAnnouncementRequest;
use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\LaunchSupplementaryAnnouncement\LaunchSupplementaryAnnouncementCommand;
use App\Announcements\Application\ListSupplementaryTemplates\ListSupplementaryTemplatesQuery;
use App\Announcements\Application\SupplementaryTemplateResult;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dispatcher launch of an airport-wide supplementary announcement (task 024): pick a
 * preset (heir of the legacy "Дополнительно" set) and a language subset, not tied to
 * a flight. The chosen preset audio is queued at priority 50 via the usual
 * post-commit publisher.
 */
#[Route('/dispatcher/supplementary-announcements')]
#[OA\Tag(name: 'Announcements')]
final class SupplementaryAnnouncementController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('/templates', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Active supplementary announcement presets',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: SupplementaryTemplateResult::class))),
            ],
            type: 'object',
        ),
    )]
    public function templates(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(
            new ListSupplementaryTemplatesQuery(active: true),
            SupplementaryTemplateResult::class,
        ));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Supplementary announcement launched',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AnnouncementResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Preset not found')]
    #[OA\Response(response: 422, description: 'Inactive preset, unconfigured language, or unavailable asset')]
    public function launch(
        #[MapRequestPayload]
        LaunchSupplementaryAnnouncementRequest $request,
    ): JsonResponse {
        return ApiResponse::created(
            $this->bus->handleAs(
                new LaunchSupplementaryAnnouncementCommand($request->templateId, $request->languages),
                AnnouncementResult::class,
            ),
            'Supplementary announcement launched successfully',
        );
    }
}
