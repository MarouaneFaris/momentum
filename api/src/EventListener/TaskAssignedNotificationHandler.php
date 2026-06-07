<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Event\TaskAssigned;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskAssignedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
        private LoggerInterface $logger,
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

        $this->publishNotification(
            $this->notificationService->create($assignee, NotificationType::TaskAssignedToYou, $basePayload)
        );

        if (!$sameUser) {
            $this->publishNotification(
                $this->notificationService->create($creator, NotificationType::TaskAssignedMember, [
                    ...$basePayload,
                    'assignee_name' => $assignee->getName(),
                ])
            );
        }
    }

    private function publishNotification(Notification $notification): void
    {
        try {
            $this->notificationPublisher->publish($notification);
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure publish failed', [
                'notification_id' => (string) $notification->getId(),
                'recipient_id' => (string) $notification->getRecipient()->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
