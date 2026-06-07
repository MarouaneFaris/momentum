<?php

declare(strict_types=1);

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

final readonly class AssigneeSummary
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string')]
        public string $name,
    ) {}
}
