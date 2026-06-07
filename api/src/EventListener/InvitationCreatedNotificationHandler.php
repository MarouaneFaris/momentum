<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Event\WorkspaceInvitationCreated;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationCreatedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(WorkspaceInvitationCreated $event): void
    {
        $invitation = $event->invitation;
        $workspace = $invitation->getWorkspace();

        $notification = $this->notificationService->create(
            $invitation->getInvitee(),
            NotificationType::InvitationReceived,
            [
                'invitation_id' => (string) $invitation->getId(),
                'workspace_id' => (string) $workspace->getId(),
                'workspace_name' => $workspace->getName(),
                'role_name' => $invitation->getRole()->value,
            ],
        );

        $this->publishNotification($notification);
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
