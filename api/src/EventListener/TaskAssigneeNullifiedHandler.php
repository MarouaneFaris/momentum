<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\ProjectMemberRemoved;
use App\Repository\TaskRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskAssigneeNullifiedHandler
{
    public function __construct(
        private TaskRepository $taskRepository,
    ) {}

    public function __invoke(ProjectMemberRemoved $event): void
    {
        $this->taskRepository->nullifyAssigneeByUserAndWorkspace(
            $event->removedMembership->getUser(),
            $event->workspace,
        );
    }
}
