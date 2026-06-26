<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\ConfiguredAnnouncementLanguagesRequest;
use App\Announcements\Application\ConfiguredAnnouncementLanguagesResult;
use App\Announcements\Application\ListConfiguredAnnouncementLanguages\ListConfiguredAnnouncementLanguagesQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/flight-announcement-configs/languages')]
#[OA\Tag(name: 'Flight announcement configs')]
final class ConfiguredAnnouncementLanguagesController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', name: 'app_flight_announcement_config_languages', methods: ['GET'])]
    #[OA\Parameter(name: 'flightDefinitionId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'announcementType', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['check_in_opening', 'check_in_continuation', 'check_in_closing', 'boarding_invitation', 'arrival']))]
    #[OA\Response(
        response: 200,
        description: 'Configured enabled languages for the flight announcement',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: ConfiguredAnnouncementLanguagesResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 422, description: 'Invalid query parameters')]
    public function languages(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ConfiguredAnnouncementLanguagesRequest $request,
    ): JsonResponse {
        return ApiResponse::success($this->bus->handleAs(
            new ListConfiguredAnnouncementLanguagesQuery($request->flightDefinitionId, $request->announcementType),
            ConfiguredAnnouncementLanguagesResult::class,
        ));
    }
}
