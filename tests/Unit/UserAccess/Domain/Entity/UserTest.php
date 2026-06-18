<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserAccess\Domain\Entity;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Event\UserRegistered;
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
}
