<?php

namespace App\UserAccess\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,

        #[Assert\NotBlank]
        public string $passwordConfirmation,
    ) {
    }

    #[Assert\IsTrue(message: 'Passwords do not match')]
    public function isPasswordConfirmed(): bool
    {
        return $this->password === $this->passwordConfirmation;
    }
}