<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\WorkspaceInvitationCreated;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationCreatedNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(WorkspaceInvitationCreated $event): void
    {
        $this->orchestrator->invitationCreated($event->invitation);
    }
}
