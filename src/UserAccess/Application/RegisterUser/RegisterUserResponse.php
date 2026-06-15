<?php
namespace App\UserAccess\Application\RegisterUser;

final readonly class RegisterUserResponse
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
    }
}