<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\TaskAssigned;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class TaskAssignedNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(TaskAssigned $event): void
    {
        $this->orchestrator->taskAssigned($event->task, $event->creator, $event->assignee);
    }
}
