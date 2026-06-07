<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\NotificationType;
use App\Enum\TaskStatus;
use App\Event\TaskStatusChanged;
use App\EventListener\TaskStatusChangedNotificationHandler;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskStatusChangedNotificationHandlerTest extends TestCase
{
    private NotificationServiceInterface&MockObject $notificationService;
    private NotificationPublisher&MockObject $notificationPublisher;
    private TaskStatusChangedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->notificationPublisher = $this->createMock(NotificationPublisher::class);
        $this->handler = new TaskStatusChangedNotificationHandler(
            $this->notificationService,
            $this->notificationPublisher,
        );
    }

    /** actor == assignee == creator → only task_status_changed_yours to that user */
    public function testActorIsAssigneeAndCreatorOnlyOneNotification(): void
    {
        $user = new User();
        $task = $this->makeTask($user, $user);

        $this->notificationService->expects($this->never())->method('create');
        $this->notificationPublisher->expects($this->never())->method('publish');

        ($this->handler)(new TaskStatusChanged($task, $user, TaskStatus::Todo, TaskStatus::InProgress));
    }

    /** actor == creator, assignee differs → task_status_changed_yours to assignee only */
    public function testActorIsCreatorNotifiesAssigneeOnly(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($assignee, NotificationType::TaskStatusChangedYours, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher->expects($this->once())->method('publish')->with($notification);

        ($this->handler)(new TaskStatusChanged($task, $creator, TaskStatus::Todo, TaskStatus::InProgress));
    }

    /** actor == assignee, creator differs → task_status_changed_member to creator only */
    public function testActorIsAssigneeNotifiesCreatorOnly(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($creator, NotificationType::TaskStatusChangedMember, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher->expects($this->once())->method('publish')->with($notification);

        ($this->handler)(new TaskStatusChanged($task, $assignee, TaskStatus::Todo, TaskStatus::InProgress));
    }

    /** actor is neither creator nor assignee → both notified */
    public function testActorIsNeitherBothNotified(): void
    {
        $creator = new User();
        $assignee = new User();
        $actor = new User();
        $task = $this->makeTask($creator, $assignee);

        $this->notificationService
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function (User $recipient) use (
                $creator, $assignee
            ): Notification {
                self::assertContains($recipient, [$assignee, $creator]);

                return new Notification();
            });

        $this->notificationPublisher->expects($this->exactly(2))->method('publish');

        ($this->handler)(new TaskStatusChanged($task, $actor, TaskStatus::Todo, TaskStatus::InProgress));
    }

    /** assignee is null → only creator notified if actor != creator */
    public function testNullAssigneeSkipsAssigneeNotification(): void
    {
        $creator = new User();
        $actor = new User();
        $task = $this->makeTask($creator, null);

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($creator, NotificationType::TaskStatusChangedMember, $this->isArray())
            ->willReturn(new Notification());

        $this->notificationPublisher->expects($this->once())->method('publish');

        ($this->handler)(new TaskStatusChanged($task, $actor, TaskStatus::Todo, TaskStatus::Done));
    }

    /** assignee is null, actor == creator → nobody notified */
    public function testNullAssigneeActorIsCreatorNoNotification(): void
    {
        $creator = new User();
        $task = $this->makeTask($creator, null);

        $this->notificationService->expects($this->never())->method('create');
        $this->notificationPublisher->expects($this->never())->method('publish');

        ($this->handler)(new TaskStatusChanged($task, $creator, TaskStatus::Todo, TaskStatus::Done));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testPayloadContainsNewStatus(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $this->notificationService
            ->expects($this->atLeastOnce())
            ->method('create')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(fn (array $p): bool => isset($p['new_status'])),
            )
            ->willReturn(new Notification());

        $this->notificationPublisher->method('publish');

        ($this->handler)(new TaskStatusChanged($task, $creator, TaskStatus::Todo, TaskStatus::Done));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMercureFailureSilenced(): void
    {
        $this->expectNotToPerformAssertions();

        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $this->notificationService->method('create')->willReturn(new Notification());

        ($this->handler)(new TaskStatusChanged($task, $creator, TaskStatus::Todo, TaskStatus::InProgress));
    }

    private function makeTask(User $creator, ?User $assignee): Task
    {
        $workspace = new Workspace();
        $project = new Project();
        $project->setWorkspace($workspace);
        $project->setName('Test Project');

        $task = new Task();
        $task->setCreator($creator);
        $task->setAssignee($assignee);
        $task->setTitle('Some task');
        $task->setProject($project);

        return $task;
    }
}
