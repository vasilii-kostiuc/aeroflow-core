<?php

namespace App\UserAccess\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LogoutUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $refreshToken,
    ) {
    }
}
