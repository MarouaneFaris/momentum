<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[OA\Property(type: 'string', maxLength: 255, example: 'Website Redesign')]
        public string $name,

        #[OA\Property(type: 'string', nullable: true, example: 'A full redesign of the marketing site')]
        public ?string $description = null,

        #[Assert\Choice(choices: ['draft', 'active', 'archived'])]
        #[OA\Property(type: 'string', nullable: true, enum: ['draft', 'active', 'archived'], example: 'active')]
        public ?string $status = null,
    ) {}
}
