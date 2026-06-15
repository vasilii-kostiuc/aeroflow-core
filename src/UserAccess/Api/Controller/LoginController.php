<?php

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[OA\Post(
        summary: 'Login',
        tags: ['User access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'dispatcher@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', nullable: true, example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Welcome to your new controller!'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: [],
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(message: 'Welcome to your new controller!');
    }
}
