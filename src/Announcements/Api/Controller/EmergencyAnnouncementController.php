<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\LaunchEmergencyAnnouncementRequest;
use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\DeactivateEmergency\DeactivateEmergencyCommand;
use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Application\GetEmergencyMode\EmergencyModeResult;
use App\Announcements\Application\GetEmergencyMode\GetEmergencyModeQuery;
use App\Announcements\Application\LaunchEmergencyAnnouncement\LaunchEmergencyAnnouncementCommand;
use App\Announcements\Application\ListEmergencyTemplates\ListEmergencyTemplatesQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dispatcher side of emergency mode (task 026): launch an emergency announcement
 * from a preset, and lift the mode when the threat is over.
 *
 * Launching is one action with two effects (the announcement at priority 1000 and
 * the activation of the mode); lifting is a separate, neutral operator command that
 * changes no announcement. The mode state itself is reported back by playback, so
 * the banner reflects what took effect, not what was requested.
 */
#[Route('/dispatcher/emergency-announcements')]
#[OA\Tag(name: 'Announcements')]
final readonly class EmergencyAnnouncementController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('/templates', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Active emergency announcement presets',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: EmergencyTemplateResult::class))),
            ],
            type: 'object',
        ),
    )]
    public function templates(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(
            new ListEmergencyTemplatesQuery(active: true),
            EmergencyTemplateResult::class,
        ));
    }

    #[Route('/mode', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Emergency mode state as last reported by playback',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: EmergencyModeResult::class)),
            ],
            type: 'object',
        ),
    )]
    public function mode(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetEmergencyModeQuery(), EmergencyModeResult::class));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Emergency announcement launched and emergency mode activated',
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
        LaunchEmergencyAnnouncementRequest $request,
    ): JsonResponse {
        return ApiResponse::created(
            $this->bus->handleAs(
                new LaunchEmergencyAnnouncementCommand($request->templateId, $request->languages),
                AnnouncementResult::class,
            ),
            'Emergency announcement launched successfully',
        );
    }

    #[Route(':deactivate', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Emergency mode deactivation requested')]
    public function deactivate(): JsonResponse
    {
        $this->bus->dispatch(new DeactivateEmergencyCommand());

        return ApiResponse::success(['requested' => true]);
    }
}
