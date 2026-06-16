<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_refresh_token')]
#[ORM\Index(name: 'IDX_REFRESH_TOKEN_USER', fields: ['user'])]
#[ORM\UniqueConstraint(name: 'UNIQ_REFRESH_TOKEN_HASH', fields: ['tokenHash'])]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $tokenHash;

    #[ORM\Column]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $replacedByHash = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(User $user, string $tokenHash, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function issue(User $user, string $tokenHash, DateTimeImmutable $expiresAt): self
    {
        return new self($user, $tokenHash, $expiresAt);
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getReplacedByHash(): ?string
    {
        return $this->replacedByHash;
    }

    public function isActive(DateTimeImmutable $now): bool
    {
        return $this->revokedAt === null && $this->expiresAt > $now;
    }

    public function revoke(?string $replacedByHash = null): void
    {
        if ($this->revokedAt !== null) {
            return;
        }

        $this->revokedAt = new DateTimeImmutable();
        $this->replacedByHash = $replacedByHash;
    }
}
