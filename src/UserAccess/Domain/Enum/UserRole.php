<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Enum;

use App\UserAccess\Domain\Exception\InvalidUserRoleException;

/**
 * What a user may do in AeroFlow (task 033). A user holds at most one role; no
 * role means the account exists but has no access. The administrator inherits
 * everything the dispatcher may do (security `role_hierarchy`).
 */
enum UserRole: string
{
    case Admin = 'ROLE_ADMIN';
    case Dispatcher = 'ROLE_DISPATCHER';

    /** Short form used by the API and the console: `admin`, `dispatcher`. */
    public function code(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::Dispatcher => 'dispatcher',
        };
    }

    public static function fromCode(string $code): self
    {
        foreach (self::cases() as $role) {
            if ($role->code() === $code) {
                return $role;
            }
        }

        throw InvalidUserRoleException::unknown($code);
    }
}
