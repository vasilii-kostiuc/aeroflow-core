<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain;

use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function testPullEventsReturnsRecordedEventsAndClearsThem(): void
    {
        $aggregate = new TestAggregateRoot();

        $aggregate->recordSomething();

        self::assertCount(1, $aggregate->pullEvents());
        self::assertSame([], $aggregate->pullEvents());
    }
}

final class TestAggregateRoot extends AggregateRoot
{
    public function recordSomething(): void
    {
        $this->recordEvent(new TestDomainEvent());
    }
}

final class TestDomainEvent implements DomainEvent
{
}
