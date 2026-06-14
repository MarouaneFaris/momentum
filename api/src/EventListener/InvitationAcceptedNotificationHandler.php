<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\WorkspaceInvitationAccepted;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationAcceptedNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(WorkspaceInvitationAccepted $event): void
    {
        $this->orchestrator->invitationAccepted($event->invitation, $event->actor);
    }
}
