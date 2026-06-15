<?php

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(message: 'Welcome to your new controller!');
    }
}
