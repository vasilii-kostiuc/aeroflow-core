<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(schema: 'SetUserPasswordRequest', required: ['password'])]
final readonly class SetUserPasswordRequest
{
    public function __construct(
        #[OA\Property(format: 'password', minLength: 8)]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
    ) {
    }
}
