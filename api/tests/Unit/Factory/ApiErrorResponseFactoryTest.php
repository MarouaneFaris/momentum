<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\Enum\ErrorCode;
use App\Factory\ApiErrorResponseFactory;
use PHPUnit\Framework\TestCase;

final class ApiErrorResponseFactoryTest extends TestCase
{
    private ApiErrorResponseFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ApiErrorResponseFactory();
    }

    public function testResponseShape(): void
    {
        $response = $this->factory->create(
            ErrorCode::AUTH_INVALID_CREDENTIALS,
            'Invalid credentials.',
            401,
        );

        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('AUTH_INVALID_CREDENTIALS', $data['code']);
        self::assertSame('Invalid credentials.', $data['message']);
        self::assertSame('{}', json_encode($data['context']));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testContextIsEmptyObjectByDefault(): void
    {
        $response = $this->factory->create(ErrorCode::REGISTRATION_FAILED, 'Registration failed.', 422);
        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('{}', json_encode($data['context']));
    }

    public function testContextPopulatedForRateLimit(): void
    {
        $response = $this->factory->create(
            ErrorCode::RATE_LIMIT_EXCEEDED,
            'Too many requests.',
            429,
            ['retry_after' => 60],
        );

        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('RATE_LIMIT_EXCEEDED', $data['code']);
        self::assertSame(60, $data['context']['retry_after']);
        self::assertSame(429, $response->getStatusCode());
    }

    public function testContextPopulatedForValidation(): void
    {
        $response = $this->factory->create(
            ErrorCode::VALIDATION_FAILED,
            'Validation error.',
            422,
            ['field' => 'email', 'violations' => ['This value is not a valid email address.']],
        );

        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('VALIDATION_FAILED', $data['code']);
        self::assertSame('email', $data['context']['field']);
        self::assertSame(['This value is not a valid email address.'], $data['context']['violations']);
    }

    public function testAllErrorCodesAreValid(): void
    {
        foreach (ErrorCode::cases() as $code) {
            $response = $this->factory->create($code, 'msg', 400);
            $data = json_decode((string) $response->getContent(), true);

            self::assertSame($code->value, $data['code']);
        }
    }
}
