<?php

declare(strict_types=1);

namespace App\Error;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ErrorResponseFactory
{
    /** @param array<string, mixed> $context */
    public function build(ErrorCode $code, string $message, array $context = [], int $status = 422): JsonResponse
    {
        return new JsonResponse(
            ['code' => $code->value, 'message' => $message, 'context' => $context],
            $status,
        );
    }
}
