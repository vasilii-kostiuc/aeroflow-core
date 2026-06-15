<?php 
namespace App\UserAccess\Domain\Event;

use App\Shared\Domain\DomainEvent;

class UserRegistered implements DomainEvent
{
    public function __construct(
        private readonly ?string $userId,
        private readonly string $email,
    ) {
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['userId'] ?? null,
            $data['email'],
        );
    }
}