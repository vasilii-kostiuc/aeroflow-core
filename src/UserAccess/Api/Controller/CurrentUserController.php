<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Application\GetCurrentUser\GetCurrentUserQuery;
use App\UserAccess\Application\UserResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/** The signed-in user with their current role; open to any authenticated account. */
#[OA\Tag(name: 'User access')]
final readonly class CurrentUserController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('/me', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current user',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: UserResult::class)),
            ],
            type: 'object',
        ),
    )]
    public function me(#[CurrentUser] UserInterface $user): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new GetCurrentUserQuery($user->getUserIdentifier()),
            UserResult::class,
        ));
    }
}
