<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Notification;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\WorkspaceInvitation;
use App\Enum\NotificationType;
use App\Enum\TaskStatus;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use App\Utils\UuidHelper;

class NotificationOrchestrator
{
    public function __construct(
        private NotificationServiceInterface $notifications,
        private NotificationPublisher $publisher,
    ) {}

    public function taskAssigned(Task $task, User $creator, User $assignee): void
    {
        $basePayload = $this->taskBasePayload($task);

        $sameUser = $creator === $assignee
            || UuidHelper::equals($creator->getId(), $assignee->getId());

        $this->createAndPublish($assignee, NotificationType::TaskAssignedToYou, $basePayload);

        if (!$sameUser) {
            $this->createAndPublish($creator, NotificationType::TaskAssignedMember, [
                ...$basePayload,
                'assignee_name' => $assignee->getName(),
            ]);
        }
    }

    public function taskStatusChanged(Task $task, User $actor, TaskStatus $newStatus): void
    {
        $basePayload = [...$this->taskBasePayload($task), 'new_status' => $newStatus->value];
        $assignee = $task->getAssignee();
        $creator = $task->getCreator();

        $actorId = $actor->getId();
        $creatorId = $creator->getId();

        $actorIsCreator = $actor === $creator
            || ($actorId !== null && $creatorId !== null && $actorId->equals($creatorId));

        $actorIsAssignee = $assignee !== null && (
            $actor === $assignee
            || ($actorId !== null && ($assigneeId = $assignee->getId()) !== null && $actorId->equals($assigneeId))
        );

        if ($assignee !== null && !$actorIsAssignee) {
            $this->createAndPublish($assignee, NotificationType::TaskStatusChangedYours, $basePayload);
        }

        $creatorIsAssignee = $assignee !== null && (
            $creator === $assignee
            || ($creatorId !== null && ($assigneeId2 = $assignee->getId()) !== null && $creatorId->equals($assigneeId2))
        );

        if (!$actorIsCreator && !$creatorIsAssignee) {
            $this->createAndPublish($creator, NotificationType::TaskStatusChangedMember, [
                ...$basePayload,
                'actor_name' => $actor->getName(),
            ]);
        }
    }

    public function invitationCreated(WorkspaceInvitation $invitation): void
    {
        $workspace = $invitation->getWorkspace();

        $this->createAndPublish(
            $invitation->getInvitee(),
            NotificationType::InvitationReceived,
            [
                'invitation_id' => (string) $invitation->getId(),
                'workspace_id' => (string) $workspace->getId(),
                'workspace_name' => $workspace->getName(),
                'role_name' => $invitation->getRole()->value,
            ],
        );
    }

    public function invitationAccepted(WorkspaceInvitation $invitation, User $actor): void
    {
        $invitedBy = $invitation->getInvitedBy();

        if ($invitedBy === null) {
            return;
        }

        $workspace = $invitation->getWorkspace();

        $this->createAndPublish($invitedBy, NotificationType::InvitationAccepted, [
            'workspace_id' => (string) $workspace->getId(),
            'workspace_name' => $workspace->getName(),
            'actor_name' => $actor->getName(),
        ]);
    }

    public function invitationCancelled(WorkspaceInvitation $invitation): void
    {
        $workspace = $invitation->getWorkspace();

        $this->createAndPublish(
            $invitation->getInvitee(),
            NotificationType::InvitationCancelled,
            [
                'workspace_id' => (string) $workspace->getId(),
                'workspace_name' => $workspace->getName(),
                'role_name' => $invitation->getRole()->value,
            ],
        );
    }

    public function invitationDeclined(WorkspaceInvitation $invitation, User $actor): void
    {
        $invitedBy = $invitation->getInvitedBy();

        if ($invitedBy === null) {
            return;
        }

        $workspace = $invitation->getWorkspace();

        $this->createAndPublish($invitedBy, NotificationType::InvitationDeclined, [
            'workspace_id' => (string) $workspace->getId(),
            'workspace_name' => $workspace->getName(),
            'actor_name' => $actor->getName(),
        ]);
    }

    public function notificationRead(Notification $notification): void
    {
        $this->notifications->markRead($notification);
        $this->publisher->publishUpdated($notification);
    }

    public function notificationDeleted(Notification $notification): void
    {
        $id = $notification->getId();
        $recipient = $notification->getRecipient();

        $this->notifications->delete($notification);

        if ($id !== null) {
            $this->publisher->publishDeleted($id, $recipient);
        }
    }

    public function allNotificationsRead(User $recipient, \DateTimeImmutable $readAt): void
    {
        $this->notifications->markAllRead($recipient);
        $this->publisher->publishAllRead($recipient, $readAt);
    }

    /** @param array<string, mixed> $payload */
    private function createAndPublish(User $recipient, NotificationType $type, array $payload): void
    {
        $this->publisher->publishCreated($this->notifications->create($recipient, $type, $payload));
    }

    /** @return array<string, mixed> */
    private function taskBasePayload(Task $task): array
    {
        $project = $task->getProject();

        return [
            'task_id' => (string) $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => (string) $project->getId(),
            'project_name' => $project->getName(),
            'workspace_id' => (string) $project->getWorkspace()->getId(),
        ];
    }
}
