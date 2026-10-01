<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Entity;

use App\Shared\Domain\AggregateRoot;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Event\UserPasswordChanged;
use App\UserAccess\Domain\Event\UserRegistered;
use App\UserAccess\Domain\Event\UserRoleChanged;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * Stored roles: empty (no access) or exactly one {@see UserRole} value.
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * The one role this user holds, or null when the account has no access.
     */
    public function getRole(): ?UserRole
    {
        foreach ($this->roles as $role) {
            $known = UserRole::tryFrom($role);
            if (null !== $known) {
                return $known;
            }
        }

        return null;
    }

    public function isAdmin(): bool
    {
        return UserRole::Admin === $this->getRole();
    }

    /**
     * Grants a role or, with null, removes access. Idempotent. Whether this user
     * is the last administrator is checked by the application layer — it needs
     * the other users.
     */
    public function changeRole(?UserRole $role): bool
    {
        if ($this->getRole() === $role) {
            return false;
        }
        $this->roles = null === $role ? [] : [$role->value];
        $this->recordEvent(new UserRoleChanged((string) $this->id, $role?->value));

        return true;
    }

    public function changePassword(string $passwordHash): void
    {
        $this->password = $passwordHash;
        $this->recordEvent(new UserPasswordChanged((string) $this->id));
    }

    public static function register(string $email, string $password, ?UserRole $role = null): self
    {
        $user = new self();
        $user->id = Uuid::v7();
        $user->email = $email;
        $user->password = $password;
        $user->roles = null === $role ? [] : [$role->value];

        $user->recordEvent(new UserRegistered($user->id->toRfc4122(), $user->email));

        return $user;
    }
}
