<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\NotificationType;
use App\Event\TaskAssigned;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskAssignedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
    ) {}

    public function __invoke(TaskAssigned $event): void
    {
        $task = $event->task;
        $creator = $event->creator;
        $assignee = $event->assignee;
        $project = $task->getProject();
        $workspace = $project->getWorkspace();

        $basePayload = [
            'task_id' => (string) $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => (string) $project->getId(),
            'project_name' => $project->getName(),
            'workspace_id' => (string) $workspace->getId(),
        ];

        $creatorId = $creator->getId();
        $assigneeId = $assignee->getId();
        $sameUser = $creator === $assignee
            || ($creatorId !== null && $assigneeId !== null && $creatorId->equals($assigneeId));

        $this->notificationPublisher->publishCreated(
            $this->notificationService->create($assignee, NotificationType::TaskAssignedToYou, $basePayload)
        );

        if (!$sameUser) {
            $this->notificationPublisher->publishCreated(
                $this->notificationService->create($creator, NotificationType::TaskAssignedMember, [
                    ...$basePayload,
                    'assignee_name' => $assignee->getName(),
                ])
            );
        }
    }
}
