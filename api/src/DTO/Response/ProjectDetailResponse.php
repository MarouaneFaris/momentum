<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Project;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class ProjectDetailTaskStats
{
    public function __construct(
        #[OA\Property(type: 'integer')]
        public int $total,
        #[OA\Property(type: 'integer')]
        public int $todo,
        #[OA\Property(type: 'integer')]
        public int $inProgress,
        #[OA\Property(type: 'integer')]
        public int $done,
    ) {}
}

final readonly class ProjectDetailResponse
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
        #[OA\Property(type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012346')]
        public string $ownerUserId,
        #[OA\Property(type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00')]
        public string $updatedAt,
        #[OA\Property(type: 'string', enum: ['blue', 'green', 'amber', 'red', 'purple', 'neutral'], example: 'blue')]
        public string $color,
        #[OA\Property(ref: new Model(type: ProjectDetailTaskStats::class))]
        public ProjectDetailTaskStats $taskStats,
        #[OA\Property(type: 'integer', example: 3)]
        public int $memberCount,
    ) {}

    /**
     * @param array{total: int, todo: int, inProgress: int, done: int} $taskStats
     */
    public static function fromProject(Project $project, array $taskStats, int $memberCount): self
    {
        return new self(
            id: (string) $project->getId(),
            name: $project->getName(),
            description: $project->getDescription(),
            status: $project->getStatus()->value,
            ownerUserId: (string) $project->getOwner()->getUser()->getId(),
            createdAt: $project->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $project->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            color: $project->getColor(),
            taskStats: new ProjectDetailTaskStats(
                total: $taskStats['total'],
                todo: $taskStats['todo'],
                inProgress: $taskStats['inProgress'],
                done: $taskStats['done'],
            ),
            memberCount: $memberCount,
        );
    }
}
