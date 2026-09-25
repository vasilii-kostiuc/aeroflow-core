<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserAccess\Domain\Entity;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Event\UserPasswordChanged;
use App\UserAccess\Domain\Event\UserRegistered;
use App\UserAccess\Domain\Event\UserRoleChanged;
use App\UserAccess\Domain\Exception\InvalidUserRoleException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function testRegisterCreatesUserWithRoleAndDomainEvent(): void
    {
        $user = User::register('dispatcher@example.com', 'hashed-password');

        self::assertInstanceOf(Uuid::class, $user->getId());
        self::assertSame('dispatcher@example.com', $user->getEmail());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $events = $user->pullEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
        self::assertSame($user->getId()?->toRfc4122(), $events[0]->userId);
        self::assertSame('dispatcher@example.com', $events[0]->email);
    }

    public function testNewUserHasNoAccessUnlessGivenARole(): void
    {
        self::assertNull(User::register('a@example.com', 'h')->getRole());

        $dispatcher = User::register('d@example.com', 'h', UserRole::Dispatcher);
        self::assertSame(UserRole::Dispatcher, $dispatcher->getRole());
        self::assertSame(['ROLE_DISPATCHER', 'ROLE_USER'], $dispatcher->getRoles());
    }

    public function testChangeRoleIsIdempotentAndCanRemoveAccess(): void
    {
        $user = User::register('a@example.com', 'h', UserRole::Dispatcher);
        $user->pullEvents();

        self::assertFalse($user->changeRole(UserRole::Dispatcher));
        self::assertSame([], $user->pullEvents());

        self::assertTrue($user->changeRole(UserRole::Admin));
        self::assertTrue($user->isAdmin());
        self::assertTrue($user->changeRole(null));
        self::assertNull($user->getRole());
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $events = $user->pullEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(UserRoleChanged::class, $events[0]);
        self::assertSame('ROLE_ADMIN', $events[0]->role);
        self::assertNull($events[1]->role);
    }

    public function testChangePasswordRecordsEvent(): void
    {
        $user = User::register('a@example.com', 'old');
        $user->pullEvents();

        $user->changePassword('new');

        self::assertSame('new', $user->getPassword());
        self::assertInstanceOf(UserPasswordChanged::class, $user->pullEvents()[0]);
    }

    public function testRoleCodes(): void
    {
        self::assertSame(UserRole::Admin, UserRole::fromCode('admin'));
        self::assertSame('dispatcher', UserRole::Dispatcher->code());

        $this->expectException(InvalidUserRoleException::class);
        UserRole::fromCode('pilot');
    }
}
