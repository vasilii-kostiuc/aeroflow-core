<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Application\Dispatcher\DispatcherBoardResult;
use App\FlightOperations\Application\Dispatcher\GetDispatcherBoardQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dispatcher/board')]
#[OA\Tag(name: 'Dispatcher')]
final class DispatcherBoardController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current operational day and one current occurrence per flight card',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: DispatcherBoardResult::class)),
            ],
            type: 'object',
        ),
    )]
    public function get(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetDispatcherBoardQuery(), DispatcherBoardResult::class));
    }
}
