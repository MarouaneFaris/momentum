<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTaskDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        #[OA\Property(type: 'string', maxLength: 255, nullable: true, example: 'Updated task title')]
        public ?string $title = null,

        #[OA\Property(type: 'string', nullable: true, example: 'Updated description')]
        public ?string $description = null,

        #[Assert\When(
            expression: 'this.status !== null',
            constraints: [new Assert\Choice(choices: ['todo', 'in-progress', 'done'])]
        )]
        #[OA\Property(type: 'string', enum: ['todo', 'in-progress', 'done'], nullable: true)]
        public ?string $status = null,

        #[Assert\Uuid]
        #[OA\Property(type: 'string', format: 'uuid', nullable: true)]
        public ?string $assigneeId = null,
    ) {}
}
