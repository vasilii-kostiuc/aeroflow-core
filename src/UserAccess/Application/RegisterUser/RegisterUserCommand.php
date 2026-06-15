<?php 
namespace App\UserAccess\Application\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}