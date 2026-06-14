<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\TaskStatusChanged;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskStatusChangedNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(TaskStatusChanged $event): void
    {
        $this->orchestrator->taskStatusChanged($event->task, $event->actor, $event->newStatus);
    }
}
