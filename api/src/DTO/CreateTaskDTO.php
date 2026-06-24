<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTaskDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[OA\Property(type: 'string', maxLength: 255, example: 'Implement login page')]
        public string $title,

        #[OA\Property(type: 'string', nullable: true, example: 'Add OAuth2 login flow')]
        public ?string $description = null,

        #[Assert\Uuid]
        #[OA\Property(type: 'string', format: 'uuid', nullable: true)]
        public ?string $assigneeId = null,

        #[Assert\Date]
        #[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-12-31')]
        public ?string $dueDate = null,
    ) {}
}
