<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\NotificationType;
use App\Event\WorkspaceInvitationCreated;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class InvitationCreatedNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private NotificationPublisher $notificationPublisher,
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

        $this->notificationPublisher->publishCreated($notification);
    }
}
