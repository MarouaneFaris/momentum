<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\UuidHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UuidHelperTest extends TestCase
{
    public function testReturnsTrueForEqualUuids(): void
    {
        $uuid = Uuid::v7();
        $copy = Uuid::fromString($uuid->toRfc4122());

        self::assertTrue(UuidHelper::equals($uuid, $copy));
    }

    public function testReturnsFalseForDifferentUuids(): void
    {
        self::assertFalse(UuidHelper::equals(Uuid::v7(), Uuid::v7()));
    }

    public function testReturnsFalseWhenFirstIsNull(): void
    {
        self::assertFalse(UuidHelper::equals(null, Uuid::v7()));
    }

    public function testReturnsFalseWhenSecondIsNull(): void
    {
        self::assertFalse(UuidHelper::equals(Uuid::v7(), null));
    }

    public function testReturnsFalseWhenBothAreNull(): void
    {
        self::assertFalse(UuidHelper::equals(null, null));
    }
}
