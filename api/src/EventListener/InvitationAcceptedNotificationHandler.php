<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Event\WorkspaceInvitationAccepted;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationAcceptedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
    ) {}

    public function __invoke(WorkspaceInvitationAccepted $event): void
    {
        $invitation = $event->invitation;
        $invitedBy = $invitation->getInvitedBy();

        if ($invitedBy === null) {
            return;
        }

        $workspace = $invitation->getWorkspace();

        $notification = $this->notificationService->create(
            $invitedBy,
            NotificationType::InvitationAccepted,
            [
                'workspace_id' => (string) $workspace->getId(),
                'workspace_name' => $workspace->getName(),
                'actor_name' => $event->actor->getName(),
            ],
        );

        $this->publishNotification($notification);
    }

    private function publishNotification(Notification $notification): void
    {
        try {
            $this->notificationPublisher->publish($notification);
        } catch (\Throwable) {
        }
    }
}
