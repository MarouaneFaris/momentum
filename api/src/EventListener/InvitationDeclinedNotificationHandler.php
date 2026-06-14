<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\WorkspaceInvitationDeclined;
use App\Notification\NotificationOrchestrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationDeclinedNotificationHandler
{
    public function __construct(
        private NotificationOrchestrator $orchestrator,
    ) {}

    public function __invoke(WorkspaceInvitationDeclined $event): void
    {
        $this->orchestrator->invitationDeclined($event->invitation, $event->actor);
    }
}
