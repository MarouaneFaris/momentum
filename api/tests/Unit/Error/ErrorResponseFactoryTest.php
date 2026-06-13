<?php

declare(strict_types=1);

namespace App\Tests\Unit\Error;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponseFactoryTest extends TestCase
{
    private ErrorResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ErrorResponseFactory();
    }

    public function testBuildReturnsCorrectShape(): void
    {
        $response = $this->factory->build(ErrorCode::VALIDATION_FAILED, 'Name is required.');

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('VALIDATION_FAILED', $body['code']);
        self::assertSame('Name is required.', $body['message']);
        self::assertSame([], $body['context']);
    }

    public function testBuildWithContext(): void
    {
        $response = $this->factory->build(
            ErrorCode::VALIDATION_FAILED,
            'Invalid field.',
            ['field' => 'email'],
        );

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame(['field' => 'email'], $body['context']);
    }

    public function testBuildWithCustomStatus(): void
    {
        $response = $this->factory->build(ErrorCode::AUTH_NOT_AUTHENTICATED, 'Not authenticated.', [], 401);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('AUTH_NOT_AUTHENTICATED', $body['code']);
    }

    public function testBuildWithInternalError(): void
    {
        $response = $this->factory->build(ErrorCode::INTERNAL_ERROR, 'An unexpected error occurred.', [], 500);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('INTERNAL_ERROR', $body['code']);
    }

    public function testResponseIsJson(): void
    {
        $response = $this->factory->build(ErrorCode::VALIDATION_FAILED, 'Error.');

        self::assertSame('application/json', $response->headers->get('Content-Type'));
    }
}
