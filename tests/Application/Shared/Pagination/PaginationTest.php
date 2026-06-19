<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\Pagination;

use App\Shared\Application\Pagination\Exception\InvalidPaginationException;
use App\Shared\Application\Pagination\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testCalculatesOffsetAndTotalPages(): void
    {
        $pagination = Pagination::fromValues(3, 20);

        self::assertSame(40, $pagination->offset());
        self::assertSame(0, $pagination->totalPagesFor(0));
        self::assertSame(1, $pagination->totalPagesFor(20));
        self::assertSame(2, $pagination->totalPagesFor(21));
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValues(int $page, int $limit): void
    {
        $this->expectException(InvalidPaginationException::class);

        Pagination::fromValues($page, $limit);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidValues(): iterable
    {
        yield 'zero page' => [0, 20];
        yield 'zero limit' => [1, 0];
        yield 'limit above maximum' => [1, 101];
    }
}
