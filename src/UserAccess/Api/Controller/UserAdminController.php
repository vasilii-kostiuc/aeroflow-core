<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Api\Request\ChangeUserRoleRequest;
use App\UserAccess\Api\Request\CreateUserRequest;
use App\UserAccess\Api\Request\SetUserPasswordRequest;
use App\UserAccess\Application\ChangeUserRole\ChangeUserRoleCommand;
use App\UserAccess\Application\CreateUser\CreateUserCommand;
use App\UserAccess\Application\ListUsers\ListUsersQuery;
use App\UserAccess\Application\SetUserPassword\SetUserPasswordCommand;
use App\UserAccess\Application\UserResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/** User management for administrators (task 033); there is no self-registration. */
#[Route('/admin/users')]
#[OA\Tag(name: 'Users')]
final readonly class UserAdminController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'All users',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: UserResult::class))),
            ],
            type: 'object',
        ),
    )]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListUsersQuery(), UserResult::class));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'User created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: UserResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 409, description: 'E-mail already taken')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function create(#[MapRequestPayload] CreateUserRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateUserCommand($r->email, $r->password, $r->role),
            UserResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'Role changed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: UserResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'The last administrator cannot lose the role')]
    public function changeRole(string $id, #[MapRequestPayload] ChangeUserRoleRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeUserRoleCommand($id, $r->role),
            UserResult::class,
        ));
    }

    #[Route('/{id}/password', methods: ['POST'])]
    #[OA\Response(response: 204, description: 'Password set; the user\'s refresh tokens are revoked')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 422, description: 'Password too short')]
    public function setPassword(string $id, #[MapRequestPayload] SetUserPasswordRequest $r): JsonResponse
    {
        $this->bus->dispatch(new SetUserPasswordCommand($id, $r->password));

        return ApiResponse::noContent();
    }
}
