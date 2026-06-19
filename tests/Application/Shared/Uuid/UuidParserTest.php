<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\Uuid;

use App\Shared\Application\Uuid\Exception\InvalidUuidException;
use App\Shared\Application\Uuid\UuidParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UuidParserTest extends TestCase
{
    public function testParsesValidUuid(): void
    {
        $value = Uuid::v7()->toRfc4122();

        self::assertSame($value, UuidParser::parse($value)->toRfc4122());
    }

    public function testRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidUuidException::class);
        $this->expectExceptionMessage('Invalid UUID "not-a-uuid".');

        UuidParser::parse('not-a-uuid');
    }
}
