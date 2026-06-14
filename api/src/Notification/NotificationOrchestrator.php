<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\WorkspaceInvitation;
use App\Enum\NotificationType;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use App\Utils\UuidHelper;

class NotificationOrchestrator
{
    public function __construct(
        private NotificationServiceInterface $notifications,
        private NotificationPublisher $publisher,
    ) {}

    public function taskAssigned(
        \App\Entity\Task $task,
        User $creator,
        User $assignee,
    ): void {
        $project = $task->getProject();
        $workspace = $project->getWorkspace();

        $basePayload = [
            'task_id' => (string) $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => (string) $project->getId(),
            'project_name' => $project->getName(),
            'workspace_id' => (string) $workspace->getId(),
        ];

        $sameUser = $creator === $assignee
            || UuidHelper::equals($creator->getId(), $assignee->getId());

        $this->publisher->publishCreated(
            $this->notifications->create($assignee, NotificationType::TaskAssignedToYou, $basePayload)
        );

        if (!$sameUser) {
            $this->publisher->publishCreated(
                $this->notifications->create($creator, NotificationType::TaskAssignedMember, [
                    ...$basePayload,
                    'assignee_name' => $assignee->getName(),
                ])
            );
        }
    }

    public function taskStatusChanged(
        \App\Entity\Task $task,
        User $actor,
        \App\Enum\TaskStatus $newStatus,
    ): void {
        $project = $task->getProject();
        $workspace = $project->getWorkspace();
        $assignee = $task->getAssignee();
        $creator = $task->getCreator();

        $basePayload = [
            'task_id' => (string) $task->getId(),
            'task_title' => $task->getTitle(),
            'project_id' => (string) $project->getId(),
            'project_name' => $project->getName(),
            'workspace_id' => (string) $workspace->getId(),
            'new_status' => $newStatus->value,
        ];

        $actorId = $actor->getId();
        $creatorId = $creator->getId();

        $actorIsCreator = $actor === $creator
            || ($actorId !== null && $creatorId !== null && $actorId->equals($creatorId));

        $actorIsAssignee = $assignee !== null && (
            $actor === $assignee
            || ($actorId !== null && ($assigneeId = $assignee->getId()) !== null && $actorId->equals($assigneeId))
        );

        if ($assignee !== null && !$actorIsAssignee) {
            $this->publisher->publishCreated(
                $this->notifications->create($assignee, NotificationType::TaskStatusChangedYours, $basePayload)
            );
        }

        $creatorIsAssignee = $assignee !== null && (
            $creator === $assignee
            || ($creatorId !== null && ($assigneeId2 = $assignee->getId()) !== null && $creatorId->equals($assigneeId2))
        );

        if (!$actorIsCreator && !$creatorIsAssignee) {
            $this->publisher->publishCreated(
                $this->notifications->create($creator, NotificationType::TaskStatusChangedMember, [
                    ...$basePayload,
                    'actor_name' => $actor->getName(),
                ])
            );
        }
    }

    public function invitationCreated(WorkspaceInvitation $invitation): void
    {
        $workspace = $invitation->getWorkspace();

        $this->publisher->publishCreated(
            $this->notifications->create(
                $invitation->getInvitee(),
                NotificationType::InvitationReceived,
                [
                    'invitation_id' => (string) $invitation->getId(),
                    'workspace_id' => (string) $workspace->getId(),
                    'workspace_name' => $workspace->getName(),
                    'role_name' => $invitation->getRole()->value,
                ],
            )
        );
    }

    public function invitationAccepted(WorkspaceInvitation $invitation, User $actor): void
    {
        $invitedBy = $invitation->getInvitedBy();

        if ($invitedBy === null) {
            return;
        }

        $workspace = $invitation->getWorkspace();

        $this->publisher->publishCreated(
            $this->notifications->create(
                $invitedBy,
                NotificationType::InvitationAccepted,
                [
                    'workspace_id' => (string) $workspace->getId(),
                    'workspace_name' => $workspace->getName(),
                    'actor_name' => $actor->getName(),
                ],
            )
        );
    }

    public function invitationCancelled(WorkspaceInvitation $invitation): void
    {
        $workspace = $invitation->getWorkspace();

        $this->publisher->publishCreated(
            $this->notifications->create(
                $invitation->getInvitee(),
                NotificationType::InvitationCancelled,
                [
                    'workspace_id' => (string) $workspace->getId(),
                    'workspace_name' => $workspace->getName(),
                    'role_name' => $invitation->getRole()->value,
                ],
            )
        );
    }

    public function invitationDeclined(WorkspaceInvitation $invitation, User $actor): void
    {
        $invitedBy = $invitation->getInvitedBy();

        if ($invitedBy === null) {
            return;
        }

        $workspace = $invitation->getWorkspace();

        $this->publisher->publishCreated(
            $this->notifications->create(
                $invitedBy,
                NotificationType::InvitationDeclined,
                [
                    'workspace_id' => (string) $workspace->getId(),
                    'workspace_name' => $workspace->getName(),
                    'actor_name' => $actor->getName(),
                ],
            )
        );
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
}
