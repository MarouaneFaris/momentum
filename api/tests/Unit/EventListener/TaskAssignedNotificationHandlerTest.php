<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\NotificationType;
use App\Event\TaskAssigned;
use App\EventListener\TaskAssignedNotificationHandler;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskAssignedNotificationHandlerTest extends TestCase
{
    private NotificationServiceInterface&MockObject $notificationService;
    private NotificationPublisher&MockObject $notificationPublisher;
    private TaskAssignedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->notificationPublisher = $this->createMock(NotificationPublisher::class);
        $this->handler = new TaskAssignedNotificationHandler(
            $this->notificationService,
            $this->notificationPublisher,
        );
    }

    public function testCreatorEqualsAssigneeOnlyOneNotification(): void
    {
        $user = new User();
        $task = $this->makeTask($user, $user);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($user, NotificationType::TaskAssignedToYou, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher
            ->expects($this->once())
            ->method('publishCreated')
            ->with($notification);

        ($this->handler)(new TaskAssigned($task, $user, $user));
    }

    public function testCreatorDiffersFromAssigneeBothNotified(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $notificationAssignee = new Notification();
        $notificationCreator = new Notification();

        $this->notificationService
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function (User $recipient, NotificationType $type) use (
                $creator, $assignee, $notificationAssignee, $notificationCreator
            ): Notification {
                if ($recipient === $assignee) {
                    self::assertSame(NotificationType::TaskAssignedToYou, $type);

                    return $notificationAssignee;
                }
                self::assertSame($creator, $recipient);
                self::assertSame(NotificationType::TaskAssignedMember, $type);

                return $notificationCreator;
            });

        $this->notificationPublisher
            ->expects($this->exactly(2))
            ->method('publishCreated');

        ($this->handler)(new TaskAssigned($task, $creator, $assignee));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMercureFailureSilenced(): void
    {
        $this->expectNotToPerformAssertions();

        $user = new User();
        $task = $this->makeTask($user, $user);

        $notification = new Notification();
        $this->notificationService->method('create')->willReturn($notification);

        ($this->handler)(new TaskAssigned($task, $user, $user));
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
        $task->setTitle('Do something');
        $task->setProject($project);

        return $task;
    }
}
