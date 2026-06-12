<?php

declare(strict_types=1);

namespace App\Error;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponseFactory
{
    /** @param array<string, mixed> $context */
    public function build(ErrorCode $code, string $message, array $context = [], int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return new JsonResponse(
            ['code' => $code->value, 'message' => $message, 'context' => $context],
            $status,
        );
    }
}
