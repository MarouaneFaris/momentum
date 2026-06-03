<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Project;
use OpenApi\Attributes as OA;

final readonly class ProjectListItemResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012345')]
        public string $id,
        #[OA\Property(type: 'string', example: 'Website Redesign')]
        public string $name,
        #[OA\Property(type: 'string', nullable: true, example: 'A full redesign of the marketing site')]
        public ?string $description,
        #[OA\Property(type: 'string', enum: ['draft', 'active', 'archived'], example: 'active')]
        public string $status,
        #[OA\Property(type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00')]
        public string $updatedAt,
    ) {}

    public static function fromProject(Project $project): self
    {
        return new self(
            id: (string) $project->getId(),
            name: $project->getName(),
            description: $project->getDescription(),
            status: $project->getStatus()->value,
            createdAt: $project->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $project->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
