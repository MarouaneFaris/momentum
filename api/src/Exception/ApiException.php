<?php

declare(strict_types=1);

namespace App\Exception;

use App\Error\ErrorCode;

final class ApiException extends \RuntimeException
{
    /** @param array<string, mixed> $context */
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
        public readonly array $context = [],
        public readonly int $httpStatus = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
