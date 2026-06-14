<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\WorkspaceInvitationCancelled;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationCancelledNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(WorkspaceInvitationCancelled $event): void
    {
        $this->orchestrator->invitationCancelled($event->invitation);
    }
}
