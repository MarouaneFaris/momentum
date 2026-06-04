<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProjectDTO
{
    public function __construct(
        #[Assert\When(
            expression: 'this.name !== null',
            constraints: [new Assert\NotBlank(), new Assert\Length(max: 255)]
        )]
        #[OA\Property(type: 'string', nullable: true, maxLength: 255, example: 'Website Redesign')]
        public ?string $name = null,

        #[OA\Property(type: 'string', nullable: true, example: 'Updated description')]
        public ?string $description = null,

        #[Assert\When(
            expression: 'this.status !== null',
            constraints: [new Assert\Choice(choices: ['draft', 'active', 'archived'])]
        )]
        #[OA\Property(type: 'string', nullable: true, enum: ['draft', 'active', 'archived'], example: 'active')]
        public ?string $status = null,
    ) {}
}
