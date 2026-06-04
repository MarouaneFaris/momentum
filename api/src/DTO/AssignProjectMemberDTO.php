<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AssignProjectMemberDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[OA\Property(type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012345')]
        public string $userId,
    ) {}
}
