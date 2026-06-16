<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefreshTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $refreshToken,
    ) {
    }
}
