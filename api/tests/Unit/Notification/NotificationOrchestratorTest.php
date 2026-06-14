<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification;

use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Enum\NotificationType;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Notification\NotificationOrchestrator;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class NotificationOrchestratorTest extends TestCase
{
    private NotificationServiceInterface&MockObject $service;
    private NotificationPublisher&MockObject $publisher;
    private NotificationOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->service = $this->createMock(NotificationServiceInterface::class);
        $this->publisher = $this->createMock(NotificationPublisher::class);
        $this->orchestrator = new NotificationOrchestrator($this->service, $this->publisher);
    }

    // --- taskAssigned ---

    public function testTaskAssignedCreatorEqualsAssigneeOneNotification(): void
    {
        $user = new User();
        $task = $this->makeTask($user, $user);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($user, NotificationType::TaskAssignedToYou, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->taskAssigned($task, $user, $user);
    }

    public function testTaskAssignedCreatorDiffersFromAssigneeBothNotified(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $notifAssignee = new Notification();
        $notifCreator = new Notification();

        $this->service
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function (User $recipient, NotificationType $type) use (
                $creator, $assignee, $notifAssignee, $notifCreator
            ): Notification {
                if ($recipient === $assignee) {
                    self::assertSame(NotificationType::TaskAssignedToYou, $type);

                    return $notifAssignee;
                }
                self::assertSame($creator, $recipient);
                self::assertSame(NotificationType::TaskAssignedMember, $type);

                return $notifCreator;
            });

        $this->publisher->expects($this->exactly(2))->method('publishCreated');

        $this->orchestrator->taskAssigned($task, $creator, $assignee);
    }

    // --- taskStatusChanged ---

    public function testTaskStatusChangedActorIsCreatorAndAssigneeNoNotification(): void
    {
        $user = new User();
        $task = $this->makeTask($user, $user);

        $this->service->expects($this->never())->method('create');
        $this->publisher->expects($this->never())->method('publishCreated');

        $this->orchestrator->taskStatusChanged($task, $user, TaskStatus::InProgress);
    }

    public function testTaskStatusChangedActorIsCreatorNotifiesAssigneeOnly(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($assignee, NotificationType::TaskStatusChangedYours, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->taskStatusChanged($task, $creator, TaskStatus::Done);
    }

    public function testTaskStatusChangedActorIsAssigneeNotifiesCreatorOnly(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($creator, NotificationType::TaskStatusChangedMember, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->taskStatusChanged($task, $assignee, TaskStatus::Done);
    }

    public function testTaskStatusChangedActorIsNeitherBothNotified(): void
    {
        $creator = new User();
        $assignee = new User();
        $actor = new User();
        $task = $this->makeTask($creator, $assignee);

        $this->service
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturn(new Notification());

        $this->publisher->expects($this->exactly(2))->method('publishCreated');

        $this->orchestrator->taskStatusChanged($task, $actor, TaskStatus::Done);
    }

    public function testTaskStatusChangedNullAssigneeActorIsCreatorNoNotification(): void
    {
        $creator = new User();
        $task = $this->makeTask($creator, null);

        $this->service->expects($this->never())->method('create');
        $this->publisher->expects($this->never())->method('publishCreated');

        $this->orchestrator->taskStatusChanged($task, $creator, TaskStatus::Done);
    }

    // --- invitation intents ---

    public function testInvitationCreatedNotifiesInvitee(): void
    {
        $invitee = new User();
        $invitation = $this->makeInvitation(invitee: $invitee);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($invitee, NotificationType::InvitationReceived, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->invitationCreated($invitation);
    }

    public function testInvitationAcceptedNotifiesInviter(): void
    {
        $inviter = new User();
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($inviter, NotificationType::InvitationAccepted, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->invitationAccepted($invitation, $actor);
    }

    public function testInvitationAcceptedSkipsWhenNoInviter(): void
    {
        $invitation = $this->makeInvitation(invitedBy: null);

        $this->service->expects($this->never())->method('create');
        $this->publisher->expects($this->never())->method('publishCreated');

        $this->orchestrator->invitationAccepted($invitation, new User());
    }

    public function testInvitationCancelledNotifiesInvitee(): void
    {
        $invitee = new User();
        $invitation = $this->makeInvitation(invitee: $invitee);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($invitee, NotificationType::InvitationCancelled, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->invitationCancelled($invitation);
    }

    public function testInvitationDeclinedNotifiesInviter(): void
    {
        $inviter = new User();
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);
        $notification = new Notification();

        $this->service
            ->expects($this->once())
            ->method('create')
            ->with($inviter, NotificationType::InvitationDeclined, $this->isArray())
            ->willReturn($notification);

        $this->publisher->expects($this->once())->method('publishCreated')->with($notification);

        $this->orchestrator->invitationDeclined($invitation, $actor);
    }

    public function testInvitationDeclinedSkipsWhenNoInviter(): void
    {
        $invitation = $this->makeInvitation(invitedBy: null);

        $this->service->expects($this->never())->method('create');
        $this->publisher->expects($this->never())->method('publishCreated');

        $this->orchestrator->invitationDeclined($invitation, new User());
    }

    // --- read / delete / all-read ---

    public function testNotificationReadMarksAndPublishes(): void
    {
        $notification = new Notification();

        $this->service->expects($this->once())->method('markRead')->with($notification);
        $this->publisher->expects($this->once())->method('publishUpdated')->with($notification);

        $this->orchestrator->notificationRead($notification);
    }

    public function testNotificationDeletedDeletesAndPublishes(): void
    {
        $id = Uuid::v7();
        $recipient = new User();
        $notification = new Notification();
        $notification->setRecipient($recipient);

        $reflection = new \ReflectionClass($notification);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($notification, $id);

        $this->service->expects($this->once())->method('delete')->with($notification);
        $this->publisher->expects($this->once())->method('publishDeleted')->with($id, $recipient);

        $this->orchestrator->notificationDeleted($notification);
    }

    public function testAllNotificationsReadMarksAndPublishes(): void
    {
        $user = new User();
        $readAt = new \DateTimeImmutable();

        $this->service->expects($this->once())->method('markAllRead')->with($user);
        $this->publisher->expects($this->once())->method('publishAllRead')->with($user, $readAt);

        $this->orchestrator->allNotificationsRead($user, $readAt);
    }

    // --- helpers ---

    private function makeTask(User $creator, ?User $assignee): Task
    {
        $workspace = new Workspace();
        $project = new Project();
        $project->setWorkspace($workspace);
        $project->setName('Test Project');

        $task = new Task();
        $task->setCreator($creator);
        $task->setAssignee($assignee);
        $task->setTitle('Do something');
        $task->setProject($project);

        return $task;
    }

    private function makeInvitation(
        ?User $invitee = null,
        ?User $invitedBy = null,
        WorkspaceRole $role = WorkspaceRole::Member,
    ): WorkspaceInvitation {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');

        $invitation = new WorkspaceInvitation();
        $invitation->setWorkspace($workspace);
        $invitation->setInvitee($invitee ?? new User());
        $invitation->setInvitedBy($invitedBy);
        $invitation->setRole($role);
        $invitation->setCreatedAt(new \DateTimeImmutable());
        $invitation->setExpiresAt(new \DateTimeImmutable('+7 days'));

        return $invitation;
    }
}
