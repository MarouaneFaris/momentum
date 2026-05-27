<?php

declare(strict_types=1);

namespace App\Factory;

use App\Enum\ErrorCode;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiErrorResponseFactory
{
    /** @param array<string, mixed> $context */
    public function create(ErrorCode $code, string $message, int $status, array $context = []): JsonResponse
    {
        return new JsonResponse(
            [
                'code' => $code->value,
                'message' => $message,
                'context' => empty($context) ? new \stdClass() : $context,
            ],
            $status,
        );
    }
}
