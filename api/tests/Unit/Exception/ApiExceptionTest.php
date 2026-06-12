<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Error\ErrorCode;
use App\Exception\ApiException;
use PHPUnit\Framework\TestCase;

final class ApiExceptionTest extends TestCase
{
    public function testDefaultHttpStatus(): void
    {
        $e = new ApiException(ErrorCode::VALIDATION_FAILED, 'Invalid input.');

        self::assertSame(422, $e->httpStatus);
        self::assertSame([], $e->context);
        self::assertSame('Invalid input.', $e->getMessage());
        self::assertSame(ErrorCode::VALIDATION_FAILED, $e->errorCode);
    }

    public function testCustomStatusAndContext(): void
    {
        $e = new ApiException(ErrorCode::WORKSPACE_FORBIDDEN, 'Forbidden.', ['reason' => 'read-only'], 403);

        self::assertSame(403, $e->httpStatus);
        self::assertSame(['reason' => 'read-only'], $e->context);
        self::assertSame(ErrorCode::WORKSPACE_FORBIDDEN, $e->errorCode);
    }

    public function testIsThrowable(): void
    {
        $e = new ApiException(ErrorCode::INTERNAL_ERROR, 'Oops.');

        self::assertInstanceOf(\Throwable::class, $e);
    }
}
