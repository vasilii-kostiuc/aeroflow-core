<?php

declare(strict_types=1);

namespace App\Localization\Api\Controller;

use App\Localization\Application\LanguageResult;
use App\Localization\Application\ListLanguages\ListLanguagesQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/languages')]
#[OA\Tag(name: 'Languages')]
final readonly class LanguageController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        summary: 'List supported languages',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Supported languages',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: LanguageResult::class)),
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListLanguagesQuery(), LanguageResult::class));
    }
}
