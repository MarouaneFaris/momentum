<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeRoleDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['member', 'guest'])]
        #[OA\Property(type: 'string', enum: ['member', 'guest'], example: 'member')]
        public string $role,
    ) {}
}
