<?php

declare(strict_types=1);

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

final readonly class TaskStatsResponse
{
    public function __construct(
        #[OA\Property(type: 'integer')]
        public int $open,
        #[OA\Property(type: 'integer')]
        public int $in_progress,
        #[OA\Property(type: 'integer')]
        public int $done_this_week,
    ) {}

    /** @param array{open: int, in_progress: int, done_this_week: int} $stats */
    public static function fromArray(array $stats): self
    {
        return new self(
            open: $stats['open'],
            in_progress: $stats['in_progress'],
            done_this_week: $stats['done_this_week'],
        );
    }
}
