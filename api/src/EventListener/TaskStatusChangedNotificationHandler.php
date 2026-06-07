<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\NotificationType;
use App\Event\TaskStatusChanged;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskStatusChangedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
    ) {}

    public function __invoke(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $actor = $event->actor;
        $project = $task->getProject();
        $workspace = $project->getWorkspace();
        $assignee = $task->getAssignee();
        $creator = $task->getCreator();

        $basePayload = [
            'task_id' => (string) $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => (string) $project->getId(),
            'project_name' => $project->getName(),
            'workspace_id' => (string) $workspace->getId(),
            'new_status' => $event->newStatus->value,
        ];

        $actorId = $actor->getId();
        $creatorId = $creator->getId();

        $actorIsCreator = $actor === $creator
            || ($actorId !== null && $creatorId !== null && $actorId->equals($creatorId));

        $actorIsAssignee = $assignee !== null && (
            $actor === $assignee
            || ($actorId !== null && ($assigneeId = $assignee->getId()) !== null && $actorId->equals($assigneeId))
        );

        // Notify assignee unless they are the actor.
        if ($assignee !== null && !$actorIsAssignee) {
            $this->notificationPublisher->publishCreated(
                $this->notificationService->create($assignee, NotificationType::TaskStatusChangedYours, $basePayload)
            );
        }

        // Notify creator unless they are the actor or same as assignee (already notified above).
        $creatorIsAssignee = $assignee !== null && (
            $creator === $assignee
            || ($creatorId !== null && ($assigneeId2 = $assignee->getId()) !== null && $creatorId->equals($assigneeId2))
        );

        if (!$actorIsCreator && !$creatorIsAssignee) {
            $this->notificationPublisher->publishCreated(
                $this->notificationService->create($creator, NotificationType::TaskStatusChangedMember, [
                    ...$basePayload,
                    'actor_name' => $actor->getName(),
                ])
            );
        }
    }
}
