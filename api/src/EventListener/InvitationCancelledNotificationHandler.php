<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\NotificationType;
use App\Event\WorkspaceInvitationCancelled;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationCancelledNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
    ) {}

    public function __invoke(WorkspaceInvitationCancelled $event): void
    {
        $invitation = $event->invitation;
        $workspace = $invitation->getWorkspace();

        $notification = $this->notificationService->create(
            $invitation->getInvitee(),
            NotificationType::InvitationCancelled,
            [
                'workspace_id' => (string) $workspace->getId(),
                'workspace_name' => $workspace->getName(),
                'role_name' => $invitation->getRole()->value,
            ],
        );

        $this->notificationPublisher->publishCreated($notification);
    }
}
