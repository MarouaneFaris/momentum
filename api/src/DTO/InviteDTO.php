<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InviteDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[OA\Property(type: 'string', format: 'email', example: 'member@example.com')]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['member', 'guest'])]
        #[OA\Property(type: 'string', enum: ['member', 'guest'], example: 'member')]
        public string $role,
    ) {}
}
