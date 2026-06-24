<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Task;
use OpenApi\Attributes as OA;

final readonly class TaskDetailResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string', example: 'Implement login page')]
        public string $title,
        #[OA\Property(type: 'string', nullable: true)]
        public ?string $description,
        #[OA\Property(type: 'string', enum: ['todo', 'in-progress', 'done'])]
        public string $status,
        public AssigneeSummary $creator,
        #[OA\Property(nullable: true)]
        public ?AssigneeSummary $assignee,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $updatedAt,
        #[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-12-31')]
        public ?string $dueDate,
    ) {}

    public static function fromTask(Task $task): self
    {
        $assignee = $task->getAssignee();
        $creator = $task->getCreator();

        return new self(
            id: (string) $task->getId(),
            title: $task->getTitle(),
            description: $task->getDescription(),
            status: $task->getStatus()->value,
            creator: new AssigneeSummary(
                id: (string) $creator->getId(),
                name: $creator->getName(),
            ),
            assignee: $assignee !== null ? new AssigneeSummary(
                id: (string) $assignee->getId(),
                name: $assignee->getName(),
            ) : null,
            createdAt: $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $task->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            dueDate: $task->getDueDate()?->toDateTimeImmutable()->format('Y-m-d'),
        );
    }
}
